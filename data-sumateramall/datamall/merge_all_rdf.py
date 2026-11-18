from rdflib import Graph, Namespace, Literal, URIRef, RDF, RDFS, XSD
import os
import re

# Namespace
EX = Namespace("http://www.example.org/mall#")

# Mapping Kota ke Provinsi
CITY_PROVINCE_MAP = {
    "Banda Aceh": "Aceh", "Lhokseumawe": "Aceh",
    "Medan": "Sumatera Utara", "Binjai": "Sumatera Utara",
    "Pematang Siantar": "Sumatera Utara", "Padang Sidempuan": "Sumatera Utara",
    "Padang": "Sumatera Barat", "Bukittinggi": "Sumatera Barat", "Payakumbuh": "Sumatera Barat",
    "Pekanbaru": "Riau", "Dumai": "Riau",
    "Jambi": "Jambi",
    "Bengkulu": "Bengkulu",
    "Palembang": "Sumatera Selatan", "Prabumulih": "Sumatera Selatan",
    "Lahat": "Sumatera Selatan", "Baturaja": "Sumatera Selatan",
    "Bandar Lampung": "Lampung",
    "Pangkal Pinang": "Bangka Belitung",
    "Batam": "Kepulauan Riau", "Tanjung Pinang": "Kepulauan Riau",
    "Baturaja": "Sumatera Selatan"
}

def slugify(text):
    return re.sub(r'[\s_-]+', '_', re.sub(r'[^\w\s-]', '', str(text).lower().strip()))

def main():
    print("="*60)
    print("MERGE ALL TTL FILES + ONTOLOGY")
    print("="*60)

    # 1. Buat Graph baru
    g = Graph()
    g.bind("ex", EX)
    g.bind("rdf", RDF)
    g.bind("rdfs", RDFS)
    g.bind("xsd", XSD)

    # 2. Tambahkan ONTOLOGI (5 Classes, 10 Obj Props, 10 Data Props)
    print("\n[1/4] Menambahkan Ontologi...")

    # 5 Classes
    classes_info = [
        ("Mall", "Pusat perbelanjaan di Pulau Sumatera"),
        ("Toko", "Toko atau tenant di dalam mall"),
        ("Kategori", "Kategori atau jenis toko"),
        ("Kota", "Kota lokasi mall"),
        ("Provinsi", "Provinsi di Pulau Sumatera")
    ]
    for cls_name, cls_comment in classes_info:
        cls_uri = EX[cls_name]
        g.add((cls_uri, RDF.type, RDFS.Class))
        g.add((cls_uri, RDFS.label, Literal(cls_name, lang="id")))
        g.add((cls_uri, RDFS.comment, Literal(cls_comment, lang="id")))
    print(f"  ✓ 5 Classes ditambahkan")

    # 10 Data Properties
    data_props_info = [
        ("namaMall", EX.Mall, XSD.string, "Nama mall"),
        ("luas", EX.Mall, XSD.string, "Luas mall"),
        ("jamBuka", EX.Mall, XSD.string, "Jam buka mall"),
        ("jumlahLantai", EX.Mall, XSD.integer, "Jumlah lantai mall"),
        ("website", EX.Mall, XSD.anyURI, "Website mall"),
        ("berlokasiDi", EX.Mall, XSD.string, "Alamat mall"),
        ("namaKota", EX.Kota, XSD.string, "Nama kota"),
        ("namaProvinsi", EX.Provinsi, XSD.string, "Nama provinsi"),
        ("namaKategori", EX.Kategori, XSD.string, "Nama kategori"),
        ("namaToko", EX.Toko, XSD.string, "Nama toko")
    ]
    for prop_name, domain, range_type, comment in data_props_info:
        prop_uri = EX[prop_name]
        g.add((prop_uri, RDF.type, RDF.Property))
        g.add((prop_uri, RDFS.domain, domain))
        g.add((prop_uri, RDFS.range, range_type))
        g.add((prop_uri, RDFS.label, Literal(prop_name, lang="id")))
        g.add((prop_uri, RDFS.comment, Literal(comment, lang="id")))
    print(f"  ✓ 10 Data Properties ditambahkan")

    # 10 Object Properties
    obj_props_info = [
        ("beradaDiKota", EX.Mall, EX.Kota, "Mall berada di kota"),
        ("beradaDiProvinsi", EX.Mall, EX.Provinsi, "Mall berada di provinsi"),
        ("memilikiKategori", EX.Mall, EX.Kategori, "Mall memiliki kategori"),
        ("memilikiToko", EX.Mall, EX.Toko, "Mall memiliki toko"),
        ("kotaTerletakDiProvinsi", EX.Kota, EX.Provinsi, "Kota terletak di provinsi"),
        ("beradaDiKotaYangSama", EX.Mall, EX.Mall, "Mall berada di kota yang sama"),
        ("kategoriSerupa", EX.Mall, EX.Mall, "Mall dengan kategori serupa"),
        ("provinsiMemilikiKota", EX.Provinsi, EX.Kota, "Provinsi memiliki kota"),
        ("kotaMemilikiMall", EX.Kota, EX.Mall, "Kota memiliki mall"),
        ("beradaDiLantai", EX.Toko, EX.Toko, "Toko berada di lantai yang sama")
    ]
    for prop_name, domain, range_type, comment in obj_props_info:
        prop_uri = EX[prop_name]
        g.add((prop_uri, RDF.type, RDF.Property))
        g.add((prop_uri, RDFS.domain, domain))
        g.add((prop_uri, RDFS.range, range_type))
        g.add((prop_uri, RDFS.label, Literal(prop_name, lang="id")))
        g.add((prop_uri, RDFS.comment, Literal(comment, lang="id")))
    print(f"  ✓ 10 Object Properties ditambahkan")

    # 3. Load SEMUA file .ttl di folder
    print("\n[2/4] Memuat semua file .ttl...")
    ttl_folder = "."
    ttl_files = [f for f in os.listdir(ttl_folder) if f.endswith('.ttl') and f != 'data_mentah.ttl']

    print(f"  Ditemukan {len(ttl_files)} file .ttl")

    total_triples_loaded = 0
    for i, ttl_file in enumerate(ttl_files):
        try:
            temp_graph = Graph()
            temp_graph.parse(os.path.join(ttl_folder, ttl_file), format='turtle')

            # Merge ke graph utama
            for triple in temp_graph:
                g.add(triple)

            total_triples_loaded += len(temp_graph)
            print(f"  [{i+1}/{len(ttl_files)}] ✓ {ttl_file} ({len(temp_graph)} triples)")
        except Exception as e:
            print(f"  [{i+1}/{len(ttl_files)}] ✗ {ttl_file} - ERROR: {e}")

    print(f"\n  Total triples dari file TTL: {total_triples_loaded}")

    # 4. Proses dan hubungkan instances (Kota, Provinsi, Kategori)
    print("\n[3/4] Memproses dan menghubungkan instances...")

    created_cities = set()
    created_provinces = set()

    # Proses Mall -> Kota -> Provinsi
    triples_to_remove = []
    mall_count = 0

    for mall, p, kota_literal in list(g.triples((None, EX.kota, None))):
        kota_name = str(kota_literal)
        prov_name = CITY_PROVINCE_MAP.get(kota_name, "Sumatera")

        city_uri = EX[f"Kota_{slugify(kota_name)}"]
        prov_uri = EX[f"Provinsi_{slugify(prov_name)}"]

        # Buat instance Kota jika belum ada
        if city_uri not in created_cities:
            g.add((city_uri, RDF.type, EX.Kota))
            g.add((city_uri, EX.namaKota, Literal(kota_name)))
            created_cities.add(city_uri)

            # Buat instance Provinsi jika belum ada
            if prov_uri not in created_provinces:
                g.add((prov_uri, RDF.type, EX.Provinsi))
                g.add((prov_uri, EX.namaProvinsi, Literal(prov_name)))
                created_provinces.add(prov_uri)

            # Hubungkan Kota ke Provinsi
            g.add((city_uri, EX.kotaTerletakDiProvinsi, prov_uri))
            g.add((prov_uri, EX.provinsiMemilikiKota, city_uri))

        # Hapus literal kota lama, tambahkan URI
        triples_to_remove.append((mall, EX.kota, kota_literal))
        g.add((mall, EX.beradaDiKota, city_uri))
        g.add((city_uri, EX.kotaMemilikiMall, mall))
        g.add((mall, EX.beradaDiProvinsi, prov_uri))
        mall_count += 1

    for t in triples_to_remove:
        g.remove(t)

    print(f"  ✓ {mall_count} mall diproses")
    print(f"  ✓ {len(created_cities)} kota dibuat")
    print(f"  ✓ {len(created_provinces)} provinsi dibuat")

    # 5. Serialize ke 2 format: Turtle (rapi) dan RDF/XML
    print("\n[4/4] Menyimpan file...")

    # Format Turtle (lebih rapi & readable)
    turtle_file = "sumateramall_complete.ttl"
    g.serialize(destination=turtle_file, format="turtle")
    print(f"  ✓ {turtle_file} ({os.path.getsize(turtle_file) / 1024:.1f} KB)")

    # Format RDF/XML (jika butuh)
    rdf_file = "sumateramall_complete.rdf"
    g.serialize(destination=rdf_file, format="xml")
    print(f"  ✓ {rdf_file} ({os.path.getsize(rdf_file) / 1024:.1f} KB)")

    print(f"\n{'='*60}")
    print(f"✅ SELESAI!")
    print(f"Total Triples: {len(g)}")
    print(f"Files created:")
    print(f"  - {turtle_file} (format Turtle - lebih rapi)")
    print(f"  - {rdf_file} (format RDF/XML)")
    print(f"{'='*60}")

if __name__ == "__main__":
    main()
