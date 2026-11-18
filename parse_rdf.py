
import json
from pathlib import Path
from rdflib import Graph, Namespace, RDF, Literal

# Lokasi file RDF
RDF_FILE = Path("data-sumateramall/datamall/sumateramall_complete.ttl")
OUTPUT_FILE = Path("storage/app/rdf_import.json")

# Namespace RDF
EX = Namespace("http://www.example.org/mall#")
NS1 = Namespace("http://sumateramall.org/ontology#")

graph = Graph()
graph.parse(RDF_FILE, format="turtle")


def get_value(subject, predicate):
    """Mengambil satu nilai properti RDF."""
    value = graph.value(subject, predicate)

    if value is None:
        return None

    return str(value).strip()


def get_uri(subject, predicate):
    """Mengambil URI relasi RDF."""
    value = graph.value(subject, predicate)

    if value is None:
        return None

    return str(value)


def get_number(subject, predicate):
    value = get_value(subject, predicate)

    if value is None:
        return None

    try:
        return int(float(value.replace(",", ".")))
    except (ValueError, TypeError):
        return None


def get_decimal(subject, predicate):
    value = get_value(subject, predicate)

    if value is None:
        return None

    try:
        return float(value.replace(",", "."))
    except (ValueError, TypeError):
        return None


def get_resources(class_uri):
    return list(graph.subjects(RDF.type, class_uri))


data = {
    "provinces": [],
    "cities": [],
    "malls": [],
    "stores": [],
    "categories": [],
    "store_categories": []
}

# ==================================================
# PROVINSI: namespace ex
# ==================================================

for subject in get_resources(EX.Provinsi):
    data["provinces"].append({
        "name": get_value(subject, EX.namaProvinsi),
        "resource_uri": str(subject)
    })

# ==================================================
# KOTA: namespace ex
# ==================================================

for subject in get_resources(EX.Kota):
    data["cities"].append({
        "name": get_value(subject, EX.namaKota),
        "province_uri": get_uri(subject, EX.kotaTerletakDiProvinsi),
        "resource_uri": str(subject)
    })

# ==================================================
# MALL: namespace ex
# ==================================================

for subject in get_resources(EX.Mall):
    data["malls"].append({
        "name": get_value(subject, EX.namaMall),
        "city_uri": get_uri(subject, EX.beradaDiKota),
        "address": get_value(subject, EX.berlokasiDi),
        "opening_hours": get_value(subject, EX.jamBuka),
        "floors": get_number(subject, EX.jumlahLantai),
        "category": get_value(subject, EX.kategori),
        "area": get_value(subject, EX.luas),
        "website": get_value(subject, EX.website),
        "description": get_value(subject, EX.deskripsiMall),
        "source_namespace": "ex",
        "resource_uri": str(subject)
    })

# ==================================================
# MALL: namespace ns1
# ==================================================

for subject in get_resources(NS1.Mall):
    data["malls"].append({
        "name": get_value(subject, NS1.namaMall),
        "city_uri": get_uri(subject, NS1.beradaDiKota),
        "address": get_value(subject, NS1.alamatMall),
        "opening_hours": get_value(subject, NS1.jamBuka),
        "floors": None,
        "category": None,
        "area": None,
        "website": None,
        "description": None,
        "source_namespace": "ns1",
        "resource_uri": str(subject)
    })

# ==================================================
# TOKO: namespace ex
# ==================================================

for subject in get_resources(EX.Toko):
    data["stores"].append({
        "name": get_value(subject, EX.namaToko),
        "mall_uri": get_uri(subject, EX.beradaDiMall),
        "category_text": get_value(subject, EX.kategoriToko),
        "logo_url": get_value(subject, EX.logoURL),
        "location": get_value(subject, EX.lokasiToko),
        "description": get_value(subject, EX.deskripsiToko),
        "status": get_value(subject, EX.statusToko),
        "opening_hours": get_value(subject, EX.jamBuka),
        "phone": None,
        "rating": None,
        "source_namespace": "ex",
        "resource_uri": str(subject)
    })

# ==================================================
# TOKO: namespace ns1
# ==================================================

for subject in get_resources(NS1.Toko):
    data["stores"].append({
        "name": get_value(subject, NS1.namaToko),
        "mall_uri": get_uri(subject, NS1.beradaDiMall),
        "category_text": get_value(subject, NS1.kategoriToko),
        "logo_url": None,
        "location": get_value(subject, NS1.lokasiToko),
        "description": None,
        "status": None,
        "opening_hours": get_value(subject, NS1.jamBuka),
        "phone": get_value(subject, NS1.nomorTelepon),
        "rating": get_decimal(subject, NS1.ratingToko),
        "source_namespace": "ns1",
        "resource_uri": str(subject)
    })

# ==================================================
# KATEGORI: namespace ns1
# ==================================================

for subject in get_resources(NS1.Kategori):
    data["categories"].append({
        "name": get_value(subject, NS1.namaKategori),
        "resource_uri": str(subject)
    })

# ==================================================
# RELASI TOKO DAN KATEGORI: namespace ns1
# ==================================================

for store in get_resources(NS1.Toko):
    for category in graph.objects(store, NS1.memilikiKategori):
        data["store_categories"].append({
            "store_uri": str(store),
            "category_uri": str(category)
        })

# ==================================================
# SIMPAN HASIL JSON
# ==================================================

OUTPUT_FILE.parent.mkdir(parents=True, exist_ok=True)

with OUTPUT_FILE.open("w", encoding="utf-8") as file:
    json.dump(data, file, ensure_ascii=False, indent=4)

print("Import RDF berhasil diproses.")
print("Total provinsi:", len(data["provinces"]))
print("Total kota:", len(data["cities"]))
print("Total mall:", len(data["malls"]))
print("Total toko:", len(data["stores"]))
print("Total kategori:", len(data["categories"]))
print("Relasi toko-kategori:", len(data["store_categories"]))
print("File JSON:", OUTPUT_FILE)
