import argparse
import re
import unicodedata
from pathlib import Path

from rdflib import Graph, Literal, Namespace, URIRef
from rdflib.namespace import OWL, RDF, RDFS, XSD


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_SOURCE = ROOT / "data-sumateramall" / "datamall"
DEFAULT_OUTPUT = ROOT / "storage" / "app" / "sumateramall-merged.ttl"
SCHEMA = Namespace("http://schema.org/")
MM = Namespace("http://sumateramall.org/ontology#")
EX = Namespace("http://www.example.org/mall#")
RESOURCE = "http://sumateramall.org/resource/"

CATEGORIES = {
    "fnb": "Food & Beverage",
    "fashion": "Fashion",
    "electronics": "Electronics",
    "entertainment": "Entertainment",
    "supermarket": "Supermarket",
    "beauty": "Beauty & Health",
    "books": "Books & Stationery",
    "accessories": "Accessories",
    "other": "Other",
}


def slugify(value):
    normalized = unicodedata.normalize("NFKD", str(value))
    ascii_value = normalized.encode("ascii", "ignore").decode("ascii").lower()
    return re.sub(r"[\W_]+", "_", ascii_value).strip("_") or "unknown"


def normalized_category(value):
    text = slugify(value)
    if any(token in text for token in ("food", "beverage", "restaurant", "cafe", "coffee", "dining", "makanan", "minuman", "bakery", "f_b")):
        return "fnb"
    if any(token in text for token in ("fashion", "clothes", "clothing", "apparel", "footwear", "shoes", "pakaian", "sepatu")):
        return "fashion"
    if any(token in text for token in ("electronic", "gadget", "smartphone", "mobile", "computer", "laptop", "telecommunication")):
        return "electronics"
    if any(token in text for token in ("entertainment", "cinema", "theatre", "theater", "karaoke", "arcade", "game")):
        return "entertainment"
    if any(token in text for token in ("supermarket", "grocery", "groceries", "convenience", "minimarket")):
        return "supermarket"
    if any(token in text for token in ("beauty", "health", "pharmacy", "cosmetic", "skincare", "clinic", "optical")):
        return "beauty"
    if any(token in text for token in ("book", "stationery", "stationary", "alat_tulis")):
        return "books"
    if any(token in text for token in ("accessor", "jewelry", "jewellery", "bag", "gift", "watch", "perhiasan", "tas")):
        return "accessories"
    return "other"


def parse_source(path):
    content = path.read_text(encoding="utf-8-sig")
    repairs = []
    if path.name == "alldta-sumateramall.ttl":
        content, count = re.subn(r"(==)>(>)\s*;", r"\1> ;", content)
        if count:
            repairs.append(f"removed {count} extra closing IRI bracket(s)")
    if path.name == "toko_plaza_medan_fair_fixed.ttl":
        malformed = 'ex:namaToko "N"DREW" ;'
        if malformed in content:
            content = content.replace(malformed, 'ex:namaToko "N\\"DREW" ;')
            repairs.append("escaped an unescaped quote in the N DREW store name")

    graph = Graph()
    try:
        if content.lstrip().startswith("<?xml") or "<rdf:RDF" in content[:500]:
            graph.parse(data=content, format="xml", publicID=path.as_uri())
        else:
            graph.parse(data=content, format="turtle", publicID=path.as_uri())
    except Exception as exc:
        raise ValueError(f"Could not parse {path.name}: {exc}") from exc

    return graph, repairs


def first_literal(graph, subject, predicate):
    value = next((item for item in graph.objects(subject, predicate) if isinstance(item, Literal) and str(item).strip()), None)
    return str(value).strip() if value is not None else ""


def province_name(graph, mall):
    province = next(graph.objects(mall, EX.beradaDiProvinsi), None)
    if province is None:
        return ""
    return (
        first_literal(graph, province, EX.namaProvinsi)
        or first_literal(graph, province, RDFS.label)
        or slugify(str(province).rsplit("#", 1)[-1]).replace("_", " ").title()
    )


def unique_resource_id(name, used_ids, fallback):
    base = slugify(name)
    candidate = base
    if candidate in used_ids and used_ids[candidate] != fallback:
        candidate = f"{base}_{slugify(fallback)}"
    used_ids[candidate] = fallback
    return candidate


def add_canonical_ontology(graph):
    class_definitions = {
        MM.Mall: "Shopping mall",
        MM.Store: "Store",
        MM.City: "City",
        MM.Province: "Province",
        MM.Category: "Store category",
    }
    for class_uri, label in class_definitions.items():
        graph.add((class_uri, RDF.type, OWL.Class))
        graph.add((class_uri, RDFS.label, Literal(label, lang="en")))

    for category_id, label in CATEGORIES.items():
        category_uri = URIRef(f"{RESOURCE}Category/{category_id}")
        graph.add((category_uri, RDF.type, MM.Category))
        graph.add((category_uri, SCHEMA.name, Literal(label, lang="id")))
        graph.add((category_uri, RDFS.label, Literal(label, lang="en")))


def normalize_legacy_data(graph):
    add_canonical_ontology(graph)
    mall_ids = {}
    used_mall_ids = {}
    city_provinces = {}

    for source_mall in sorted(set(graph.subjects(RDF.type, EX.Mall)), key=str):
        name = first_literal(graph, source_mall, EX.namaMall)
        city = first_literal(graph, source_mall, EX.kota)
        province = province_name(graph, source_mall)
        if not name or not city or not province:
            continue

        source_id = str(source_mall).rsplit("#", 1)[-1]
        mall_id = unique_resource_id(name, used_mall_ids, source_id)
        mall_uri = URIRef(f"{RESOURCE}Mall/{mall_id}")
        city_uri = URIRef(f"{RESOURCE}City/{slugify(city)}")
        province_uri = URIRef(f"{RESOURCE}Province/{slugify(province)}")
        mall_ids[source_mall] = mall_uri
        city_provinces[(city, province)] = city_uri

        graph.add((mall_uri, RDF.type, MM.Mall))
        graph.add((mall_uri, SCHEMA.name, Literal(name, lang="id")))
        graph.add((mall_uri, MM.inCity, city_uri))
        graph.add((mall_uri, MM.inProvince, province_uri))
        graph.add((city_uri, RDF.type, MM.City))
        graph.add((city_uri, SCHEMA.name, Literal(city, lang="id")))
        graph.add((city_uri, RDFS.label, Literal(city, lang="en")))
        graph.add((city_uri, MM.inProvince, province_uri))
        graph.add((province_uri, RDF.type, MM.Province))
        graph.add((province_uri, SCHEMA.name, Literal(province, lang="id")))
        graph.add((province_uri, RDFS.label, Literal(province, lang="en")))

        address = first_literal(graph, source_mall, EX.berlokasiDi)
        website = next(graph.objects(source_mall, EX.website), None)
        hours = first_literal(graph, source_mall, EX.jamBuka)
        if address:
            graph.add((mall_uri, SCHEMA.address, Literal(address, lang="id")))
        if isinstance(website, URIRef):
            graph.add((mall_uri, SCHEMA.url, website))
        if hours:
            graph.add((mall_uri, MM.openingHoursText, Literal(hours, lang="id")))
        floors = first_literal(graph, source_mall, EX.jumlahLantai)
        if floors.isdigit():
            graph.add((mall_uri, MM.numberOfFloors, Literal(int(floors), datatype=XSD.integer)))

    used_store_ids = set()
    for source_store in sorted(set(graph.subjects(RDF.type, EX.Toko)), key=str):
        source_mall = next(graph.objects(source_store, EX.beradaDiMall), None)
        mall_uri = mall_ids.get(source_mall)
        name = first_literal(graph, source_store, EX.namaToko)
        if mall_uri is None or not name:
            continue

        source_id = str(source_store).rsplit("#", 1)[-1]
        store_id = slugify(source_id)
        if store_id in used_store_ids:
            store_id = f"{store_id}_{len(used_store_ids)}"
        used_store_ids.add(store_id)
        store_uri = URIRef(f"{RESOURCE}Store/{store_id}")
        category_text = first_literal(graph, source_store, EX.kategoriToko)
        category_id = normalized_category(category_text)
        category_uri = URIRef(f"{RESOURCE}Category/{category_id}")
        floor = first_literal(graph, source_store, EX.lokasiToko)
        logo = next(graph.objects(source_store, EX.logoURL), None)

        graph.add((store_uri, RDF.type, MM.Store))
        graph.add((store_uri, SCHEMA.name, Literal(name, lang="id")))
        graph.add((store_uri, MM.locatedIn, mall_uri))
        graph.add((store_uri, MM.hasCategory, category_uri))
        graph.add((store_uri, SCHEMA.category, Literal(category_text, lang="id")))
        graph.add((store_uri, MM.sourceResource, source_store))
        if floor:
            graph.add((store_uri, MM.floor, Literal(floor, lang="id")))
        if isinstance(logo, URIRef):
            graph.add((store_uri, SCHEMA.image, logo))

    return len(mall_ids), len(used_store_ids)


def merge_dataset(source_dir=DEFAULT_SOURCE, output_path=DEFAULT_OUTPUT):
    files = sorted(Path(source_dir).glob("*.ttl"))
    if not files:
        raise FileNotFoundError(f"No Turtle files found in {source_dir}")

    complete_file = next((path for path in files if path.name == "sumateramall_complete.ttl"), None)
    if complete_file is not None:
        files.remove(complete_file)
        files.insert(0, complete_file)

    merged = Graph()
    total_repairs = []
    for path in files:
        try:
            source_graph, repairs = parse_source(path)
        except ValueError:
            if path.name == "alldta-sumateramall.ttl" and complete_file is not None:
                print("Skipped alldta-sumateramall.ttl: malformed; its mall records are covered by sumateramall_complete.ttl.")
                continue
            raise
        merged += source_graph
        total_repairs.extend(f"{path.name}: {repair}" for repair in repairs)
        print(f"Loaded {path.name}: {len(source_graph)} triples")

    mall_count, store_count = normalize_legacy_data(merged)
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    merged.bind("mm", MM)
    merged.bind("schema", SCHEMA)
    merged.serialize(destination=output_path, format="turtle")
    print(f"Merged triples: {len(merged)}")
    print(f"Normalized malls: {mall_count}; normalized stores: {store_count}")
    print(f"Output: {output_path}")
    for repair in total_repairs:
        print(f"Repaired in memory: {repair}")
    return merged


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Merge and normalize the supplied SumateraMall Turtle dataset.")
    parser.add_argument("--source-dir", type=Path, default=DEFAULT_SOURCE)
    parser.add_argument("--output", type=Path, default=DEFAULT_OUTPUT)
    arguments = parser.parse_args()
    merge_dataset(arguments.source_dir, arguments.output)
