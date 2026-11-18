from rdflib import Graph, Namespace, Literal, URIRef, RDF, RDFS, XSD
import re

EX = Namespace("http://www.example.org/mall#")

CITY_PROVINCE_MAP = {
    "Banda Aceh": "Aceh", "Lhokseumawe": "Aceh",
    "Medan": "Sumatera Utara", "Binjai": "Sumatera Utara", "Pematang Siantar": "Sumatera Utara", "Padang Sidempuan": "Sumatera Utara",
    "Padang": "Sumatera Barat", "Bukittinggi": "Sumatera Barat", "Payakumbuh": "Sumatera Barat",
    "Pekanbaru": "Riau", "Dumai": "Riau",
    "Jambi": "Jambi",
    "Bengkulu": "Bengkulu",
    "Palembang": "Sumatera Selatan", "Prabumulih": "Sumatera Selatan", "Lahat": "Sumatera Selatan", "Baturaja": "Sumatera Selatan",
    "Bandar Lampung": "Lampung",
    "Pangkal Pinang": "Bangka Belitung",
    "Batam": "Kepulauan Riau", "Tanjung Pinang": "Kepulauan Riau"
}

def slugify(text):
    return re.sub(r'[\s_-]+', '_', re.sub(r'[^\w\s-]', '', str(text).lower().strip()))

def main():
    print("=== MEMULAI PROSES ===")

    # 1. Load data mentah
    print("[1/5] Memuat data_mentah.ttl...")
    g = Graph()
    g.parse("data_mentah.ttl", format="turtle")
    print(f"  ✓ Loaded {len(g)} triples")

    # 2. Tambahkan 5 Classes
    print("[2/5] Menambahkan 5 Classes...")
    for c in ["Mall", "Kota", "Provinsi", "Kategori", "Toko"]:
        g.add((EX[c], RDF.type, RDFS.Class))
        g.add((EX[c], RDFS.label, Literal(c, lang="id")))

    # 3. Tambahkan 10 Data Properties
    print("[3/5] Menambahkan 10 Data Properties...")
    data_props = [
        ("namaMall", EX.Mall, XSD.string), ("luas", EX.Mall, XSD.string),
        ("jamBuka", EX.Mall, XSD.string), ("jumlahLantai", EX.Mall, XSD.integer),
        ("website", EX.Mall, XSD.anyURI), ("berlokasiDi", EX.Mall, XSD.string),
        ("namaKota", EX.Kota, XSD.string), ("namaProvinsi", EX.Provinsi, XSD.string),
        ("namaKategori", EX.Kategori, XSD.string), ("ratingToko", EX.Toko, XSD.float)
    ]
    for prop, domain, range_type in data_props:
        g.add((EX[prop], RDF.type, RDF.Property))
        g.add((EX[prop], RDFS.domain, domain))
        g.add((EX[prop], RDFS.range, range_type))
        g.add((EX[prop], RDFS.label, Literal(prop, lang="id")))

    # 4. Tambahkan 10 Object Properties
    print("[4/5] Menambahkan 10 Object Properties...")
    obj_props = [
        ("beradaDiKota", EX.Mall, EX.Kota), ("beradaDiProvinsi", EX.Mall, EX.Provinsi),
        ("memilikiKategori", EX.Mall, EX.Kategori), ("memilikiToko", EX.Mall, EX.Toko),
        ("kotaTerletakDiProvinsi", EX.Kota, EX.Provinsi), ("beradaDiKotaYangSama", EX.Mall, EX.Mall),
        ("kategoriSerupa", EX.Mall, EX.Mall), ("provinsiMemilikiKota", EX.Provinsi, EX.Kota),
        ("kotaMemilikiMall", EX.Kota, EX.Mall), ("beradaDiLantai", EX.Toko, EX.Toko)
    ]
    for prop, domain, range_type in obj_props:
        g.add((EX[prop], RDF.type, RDF.Property))
        g.add((EX[prop], RDFS.domain, domain))
        g.add((EX[prop], RDFS.range, range_type))
        g.add((EX[prop], RDFS.label, Literal(prop, lang="id")))

    # 5. Proses Instances: Kota, Provinsi, Kategori
    print("[5/5] Memproses instances...")
    created_cities = set()
    created_provinces = set()
    created_categories = set()

    # Proses Kota & Provinsi
    triples_to_remove_city = []
    for mall, p, kota_literal in list(g.triples((None, EX.kota, None))):
        kota_name = str(kota_literal)
        prov_name = CITY_PROVINCE_MAP.get(kota_name, "Sumatera")

        city_uri = EX[f"Kota_{slugify(kota_name)}"]
        prov_uri = EX[f"Provinsi_{slugify(prov_name)}"]

        if city_uri not in created_cities:
            g.add((city_uri, RDF.type, EX.Kota))
            g.add((city_uri, EX.namaKota, Literal(kota_name)))
            created_cities.add(city_uri)

            if prov_uri not in created_provinces:
                g.add((prov_uri, RDF.type, EX.Provinsi))
                g.add((prov_uri, EX.namaProvinsi, Literal(prov_name)))
                created_provinces.add(prov_uri)

            g.add((city_uri, EX.kotaTerletakDiProvinsi, prov_uri))
            g.add((prov_uri, EX.provinsiMemilikiKota, city_uri))

        triples_to_remove_city.append((mall, EX.kota, kota_literal))
        g.add((mall, EX.beradaDiKota, city_uri))
        g.add((city_uri, EX.kotaMemilikiMall, mall))
        g.add((mall, EX.beradaDiProvinsi, prov_uri))

    for t in triples_to_remove_city:
        g.remove(t)

    # Proses Kategori
    triples_to_remove_kat = []
    for mall, p, kat_literal in list(g.triples((None, EX.kategori, None))):
        kat_name = str(kat_literal)
        kat_uri = EX[f"Kategori_{slugify(kat_name)}"]

        if kat_uri not in created_categories:
            g.add((kat_uri, RDF.type, EX.Kategori))
            g.add((kat_uri, EX.namaKategori, Literal(kat_name)))
            created_categories.add(kat_uri)

        triples_to_remove_kat.append((mall, EX.kategori, kat_literal))
        g.add((mall, EX.memilikiKategori, kat_uri))

    for t in triples_to_remove_kat:
        g.remove(t)

    # Tambahkan contoh instances Toko (sederhana)
    print("  Menambahkan contoh Toko...")
    sample_mall = URIRef("http://www.example.org/mall#Mall15")
    toko1 = EX["Toko_starbucks"]
    toko2 = EX["Toko_uniqlo"]

    g.add((toko1, RDF.type, EX.Toko))
    g.add((toko1, EX.namaToko, Literal("Starbucks Coffee")))
    g.add((toko1, EX.ratingToko, Literal(4.5, datatype=XSD.float)))
    g.add((sample_mall, EX.memilikiToko, toko1))

    g.add((toko2, RDF.type, EX.Toko))
    g.add((toko2, EX.namaToko, Literal("Uniqlo")))
    g.add((toko2, EX.ratingToko, Literal(4.8, datatype=XSD.float)))
    g.add((sample_mall, EX.memilikiToko, toko2))
    g.add((toko1, EX.beradaDiLantai, toko2))

    # Contoh object property antar mall
    mall1 = URIRef("http://www.example.org/mall#Mall15")
    mall2 = URIRef("http://www.example.org/mall#Mall84")
    g.add((mall1, EX.beradaDiKotaYangSama, mall2))
    g.add((mall1, EX.kategoriSerupa, mall2))

    # 6. Serialize ke RDF/XML
    print("\n[FINAL] Menyimpan ke sumateramall_final.rdf...")
    g.serialize(destination="sumateramall_final.rdf", format="xml")

    print(f"\n{'='*50}")
    print(f"✅ BERHASIL!")
    print(f"File: sumateramall_final.rdf")
    print(f"Total Triples: {len(g)}")
    print(f"{'='*50}")

if __name__ == "__main__":
    main()
