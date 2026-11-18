import argparse
import json
import re
from pathlib import Path

from rdflib import BNode, Graph, Literal, Namespace, URIRef
from rdflib.namespace import OWL, RDF, RDFS, XSD


SCRIPT_DIR = Path(__file__).resolve().parent
SCHEMA = Namespace("http://schema.org/")
MM = Namespace("http://sumateramall.org/ontology#")
RESOURCE = "http://sumateramall.org/resource/"
DAYS = {
    "Mo": "Monday",
    "Tu": "Tuesday",
    "We": "Wednesday",
    "Th": "Thursday",
    "Fr": "Friday",
    "Sa": "Saturday",
    "Su": "Sunday",
}

CATEGORIES = {
    "fnb": ("Food & Beverage", {"fnb", "food_beverage", "food_and_beverage", "restaurant", "cafe", "fast_food"}),
    "fashion": ("Fashion", {"fashion", "clothes", "shoes", "bag", "jewelry"}),
    "electronics": ("Electronics", {"electronics", "mobile_phone", "computer"}),
    "entertainment": ("Entertainment", {"entertainment", "cinema", "theatre"}),
    "supermarket": ("Supermarket", {"supermarket", "convenience"}),
    "beauty": ("Beauty & Health", {"beauty", "health", "pharmacy", "chemist"}),
    "books": ("Books & Stationery", {"books", "book", "stationery"}),
    "accessories": ("Accessories", {"accessories"}),
    "other": ("Other", {"other", "mall"}),
}


def slugify(text):
    normalized = re.sub(r"[^\w\s-]", "", str(text).lower().strip())
    return re.sub(r"[\s_-]+", "_", normalized)


def make_uri(type_name, name):
    return URIRef(f"{RESOURCE}{type_name}/{slugify(name)}")


def category_for(name):
    normalized = slugify(name)
    for category_id, (label, aliases) in CATEGORIES.items():
        if normalized == category_id or normalized in aliases:
            return category_id, label
    return "other", CATEGORIES["other"][0]


def add_ontology(graph):
    for class_name, label in [
        ("Mall", "Shopping mall"),
        ("Store", "Store"),
        ("City", "City"),
        ("Province", "Province"),
        ("Category", "Store category"),
    ]:
        class_uri = MM[class_name]
        graph.add((class_uri, RDF.type, OWL.Class))
        graph.add((class_uri, RDFS.label, Literal(label, lang="en")))

    object_properties = {
        "inCity": (MM.Mall, MM.City),
        "locatedIn": (MM.Store, MM.Mall),
        "hasCategory": (MM.Store, MM.Category),
    }
    for property_name, (domain, range_value) in object_properties.items():
        property_uri = MM[property_name]
        graph.add((property_uri, RDF.type, OWL.ObjectProperty))
        graph.add((property_uri, RDFS.domain, domain))
        graph.add((property_uri, RDFS.range, range_value))

    graph.add((MM.inProvince, RDF.type, OWL.ObjectProperty))
    graph.add((MM.inProvince, RDFS.range, MM.Province))

    data_properties = {
        "wheelchairAccessible": XSD.boolean,
        "hasParking": XSD.boolean,
        "hasElevator": XSD.boolean,
        "rating": XSD.decimal,
        "priceLevel": XSD.integer,
        "businessStatus": XSD.string,
    }
    for property_name, range_value in data_properties.items():
        property_uri = MM[property_name]
        graph.add((property_uri, RDF.type, OWL.DatatypeProperty))
        graph.add((property_uri, RDFS.range, range_value))


def add_boolean_property(graph, subject, property_uri, value):
    if value is None or value == "":
        return
    if isinstance(value, bool):
        parsed = value
    else:
        normalized = str(value).strip().lower()
        if normalized not in {"yes", "no", "true", "false", "1", "0"}:
            return
        parsed = normalized in {"yes", "true", "1"}
    graph.add((subject, property_uri, Literal(parsed, datatype=XSD.boolean)))


def parse_opening_hours(value):
    if not isinstance(value, str) or not value.strip():
        return []
    if value.strip() == "24/7":
        return [(day, "00:00:00", "23:59:59") for day in DAYS.values()]

    schedules = []
    for rule in value.split(";"):
        match = re.fullmatch(
            r"\s*((?:Mo|Tu|We|Th|Fr|Sa|Su)(?:[-,](?:Mo|Tu|We|Th|Fr|Sa|Su))*)\s+(.+?)\s*",
            rule,
        )
        if not match:
            continue

        day_specification, time_specification = match.groups()
        selected_days = set()
        for day_group in day_specification.split(","):
            if "-" in day_group:
                start, end = day_group.split("-", 1)
                ordered_days = list(DAYS)
                start_index = ordered_days.index(start)
                end_index = ordered_days.index(end)
                indexes = (
                    range(start_index, end_index + 1)
                    if start_index <= end_index
                    else list(range(start_index, len(ordered_days))) + list(range(0, end_index + 1))
                )
                selected_days.update(ordered_days[index] for index in indexes)
            else:
                selected_days.add(day_group)

        for period in time_specification.split(","):
            time_match = re.fullmatch(r"\s*(\d{1,2}:\d{2})-(\d{1,2}:\d{2})\s*", period)
            if not time_match:
                continue
            opens, closes = time_match.groups()
            if not valid_time(opens) or not valid_time(closes):
                continue
            if opens == "24:00":
                continue

            for day_code in DAYS:
                if day_code in selected_days:
                    open_minutes = minutes_since_midnight(opens)
                    close_minutes = minutes_since_midnight(closes)
                    if close_minutes < open_minutes:
                        schedules.append((DAYS[day_code], normalize_time(opens), "23:59:59"))
                        next_day = list(DAYS)[(list(DAYS).index(day_code) + 1) % len(DAYS)]
                        schedules.append((DAYS[next_day], "00:00:00", normalize_time(closes)))
                    else:
                        normalized_close = "23:59:59" if closes == "24:00" else normalize_time(closes)
                        schedules.append((DAYS[day_code], normalize_time(opens), normalized_close))

    return schedules


def valid_time(value):
    hour, minute = (int(part) for part in value.split(":"))
    return 0 <= hour <= 24 and 0 <= minute <= 59 and (hour < 24 or minute == 0)


def normalize_time(value):
    hour, minute = (int(part) for part in value.split(":"))
    return f"{hour:02d}:{minute:02d}:00"


def minutes_since_midnight(value):
    hour, minute = (int(part) for part in value.split(":"))
    return hour * 60 + minute


def add_opening_hours(graph, store_uri, value):
    for day, opens, closes in parse_opening_hours(value):
        specification = BNode()
        graph.add((specification, RDF.type, SCHEMA.OpeningHoursSpecification))
        graph.add((specification, SCHEMA.dayOfWeek, URIRef(f"http://schema.org/{day}")))
        graph.add((specification, SCHEMA.opens, Literal(opens, datatype=XSD.time)))
        graph.add((specification, SCHEMA.closes, Literal(closes, datatype=XSD.time)))
        graph.add((store_uri, SCHEMA.openingHoursSpecification, specification))


def main(output_path=None):
    malls_path = SCRIPT_DIR / "malls_sumatera.json"
    stores_path = SCRIPT_DIR / "stores_sumatera.json"
    output_path = Path(output_path) if output_path else SCRIPT_DIR.parent / "ontology" / "sumateramall-data.ttl"

    with malls_path.open(encoding="utf-8") as file:
        malls = json.load(file)
    with stores_path.open(encoding="utf-8") as file:
        stores = json.load(file)

    graph = Graph()
    graph.bind("schema", SCHEMA)
    graph.bind("mm", MM)
    add_ontology(graph)

    cities = set()
    provinces = set()
    for mall in malls:
        if not isinstance(mall, dict):
            continue
        province = mall.get("province", "").strip()
        city = mall.get("city", "").strip()
        if province:
            provinces.add(province)
        if city and province:
            cities.add((city, province))

    for province in provinces:
        uri = make_uri("Province", province)
        graph.add((uri, RDF.type, MM.Province))
        graph.add((uri, SCHEMA.name, Literal(province, lang="id")))
        graph.add((uri, RDFS.label, Literal(province, lang="en")))

    for city, province in cities:
        uri = make_uri("City", city)
        graph.add((uri, RDF.type, MM.City))
        graph.add((uri, SCHEMA.name, Literal(city, lang="id")))
        graph.add((uri, RDFS.label, Literal(city, lang="en")))
        graph.add((uri, MM.inProvince, make_uri("Province", province)))

    for category_id, (label, _) in CATEGORIES.items():
        uri = URIRef(f"{RESOURCE}Category/{category_id}")
        graph.add((uri, RDF.type, MM.Category))
        graph.add((uri, SCHEMA.name, Literal(label, lang="id")))
        graph.add((uri, RDFS.label, Literal(label, lang="en")))

    for mall in malls:
        if not isinstance(mall, dict):
            continue
        name = mall.get("name", "").strip()
        province = mall.get("province", "").strip()
        city = mall.get("city", "").strip()
        if not name or not province or not city:
            continue

        uri = make_uri("Mall", mall.get("id") or name)
        graph.add((uri, RDF.type, MM.Mall))
        graph.add((uri, SCHEMA.name, Literal(name, lang="id")))
        graph.add((uri, MM.inProvince, make_uri("Province", province)))
        graph.add((uri, MM.inCity, make_uri("City", city)))

        description = mall.get("description")
        if description:
            graph.add((uri, SCHEMA.description, Literal(description, lang="id")))
        address = mall.get("address") or mall.get("display_name")
        if address:
            graph.add((uri, SCHEMA.address, Literal(address, lang="id")))
        if mall.get("lat") is not None and mall.get("lng") is not None:
            geo = URIRef(f"{uri}/geo")
            graph.add((geo, RDF.type, SCHEMA.GeoCoordinates))
            graph.add((geo, SCHEMA.latitude, Literal(float(mall["lat"]), datatype=XSD.decimal)))
            graph.add((geo, SCHEMA.longitude, Literal(float(mall["lng"]), datatype=XSD.decimal)))
            graph.add((uri, SCHEMA.geo, geo))

        for field, predicate in [
            ("wheelchair_accessible", MM.wheelchairAccessible),
            ("wheelchair", MM.wheelchairAccessible),
            ("parking", MM.hasParking),
            ("has_parking", MM.hasParking),
            ("elevator", MM.hasElevator),
            ("has_elevator", MM.hasElevator),
        ]:
            add_boolean_property(graph, uri, predicate, mall.get(field))

    for store in stores:
        if not isinstance(store, dict):
            continue
        store_id = store.get("id")
        name = store.get("name")
        mall_name = store.get("mall_name")
        if not store_id or not name or not mall_name:
            continue

        store_uri = make_uri("Store", store_id)
        category_id, _ = category_for(store.get("category", "Other"))
        graph.add((store_uri, RDF.type, MM.Store))
        graph.add((store_uri, SCHEMA.name, Literal(name, lang="id")))
        graph.add((store_uri, MM.locatedIn, make_uri("Mall", mall_name)))
        graph.add((store_uri, MM.hasCategory, URIRef(f"{RESOURCE}Category/{category_id}")))

        rating = store.get("rating")
        if rating is not None and rating != "":
            try:
                rating_value = float(rating)
                if 0 <= rating_value <= 5:
                    graph.add((store_uri, MM.rating, Literal(rating_value, datatype=XSD.decimal)))
            except (TypeError, ValueError):
                pass

        price_level = store.get("price_level")
        if price_level is not None and price_level != "":
            try:
                price_value = int(price_level)
                if 1 <= price_value <= 4:
                    graph.add((store_uri, MM.priceLevel, Literal(price_value, datatype=XSD.integer)))
            except (TypeError, ValueError):
                pass

        status = store.get("business_status") or store.get("status")
        if status:
            graph.add((store_uri, MM.businessStatus, Literal(str(status), datatype=XSD.string)))

        add_opening_hours(graph, store_uri, store.get("opening_hours"))

        if store.get("lat") is not None and store.get("lng") is not None:
            geo = URIRef(f"{store_uri}/geo")
            graph.add((geo, RDF.type, SCHEMA.GeoCoordinates))
            graph.add((geo, SCHEMA.latitude, Literal(float(store["lat"]), datatype=XSD.decimal)))
            graph.add((geo, SCHEMA.longitude, Literal(float(store["lng"]), datatype=XSD.decimal)))
            graph.add((store_uri, SCHEMA.geo, geo))

    output_path.parent.mkdir(parents=True, exist_ok=True)
    graph.serialize(destination=output_path, format="turtle")
    print(f"Malls: {len(malls)}, stores: {len(stores)}, RDF triples: {len(graph)}")
    print(f"RDF written to {output_path}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Convert the mall and store JSON files to RDF/Turtle.")
    parser.add_argument("--output", type=Path, help="Optional Turtle output path.")
    main(parser.parse_args().output)
