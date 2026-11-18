import json
import re

def slugify(text):
    text = str(text).lower().strip()
    text = re.sub(r'[^\w\s-]', '', text)
    text = re.sub(r'[\s_-]+', '_', text)
    return text

def escape_xml(text):
    """Escape karakter khusus XML"""
    if not text:
        return ""
    text = str(text)
    text = text.replace("&", "&amp;")
    text = text.replace("<", "&lt;")
    text = text.replace(">", "&gt;")
    text = text.replace('"', "&quot;")
    text = text.replace("'", "&apos;")
    return text

def generate_rdf_xml():
    # Load data
    with open('malls_sumatera.json', 'r', encoding='utf-8') as f:
        malls = json.load(f)

    with open('stores_sumatera.json', 'r', encoding='utf-8') as f:
        stores = json.load(f)

    # Mulai generate RDF/XML
    rdf_content = '''<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
    xmlns:ex="http://www.example.org/mall#">

'''

    # Collect unique provinces and cities
    provinces = set()
    cities = set()
    for mall in malls:
        if isinstance(mall, dict):
            prov = mall.get('province', '')
            city = mall.get('city', '')
            if prov:
                provinces.add(prov)
            if city and prov:
                cities.add((city, prov))

    # Generate Province instances
    print("Generating provinces...")
    for prov in sorted(provinces):
        prov_id = slugify(prov)
        rdf_content += f'''    <!-- Province: {escape_xml(prov)} -->
    <ex:Provinsi rdf:about="http://www.example.org/resource/Provinsi/{prov_id}">
        <ex:namaProvinsi>{escape_xml(prov)}</ex:namaProvinsi>
    </ex:Provinsi>

'''

    # Generate City instances
    print("Generating cities...")
    for city, prov in sorted(cities):
        city_id = slugify(city)
        prov_id = slugify(prov)
        rdf_content += f'''    <!-- City: {escape_xml(city)} -->
    <ex:Kota rdf:about="http://www.example.org/resource/Kota/{city_id}">
        <ex:namaKota>{escape_xml(city)}</ex:namaKota>
        <ex:beradaDiProvinsi rdf:resource="http://www.example.org/resource/Provinsi/{prov_id}"/>
    </ex:Kota>

'''

    # Generate Category instances
    print("Generating categories...")
    categories = set()
    for store in stores:
        if isinstance(store, dict):
            cat = store.get('category', 'Other')
            if cat:
                categories.add(cat)

    for cat in sorted(categories):
        cat_id = slugify(cat)
        rdf_content += f'''    <!-- Category: {escape_xml(cat)} -->
    <ex:Kategori rdf:about="http://www.example.org/resource/Kategori/{cat_id}">
        <ex:namaKategori>{escape_xml(cat)}</ex:namaKategori>
    </ex:Kategori>

'''

    # Generate Mall instances
    print("Generating malls...")
    for mall in malls:
        if not isinstance(mall, dict):
            continue
        name = mall.get('name', '')
        province = mall.get('province', '')
        city = mall.get('city', '')
        address = mall.get('address', '')
        description = mall.get('description', '')

        if not name or not province or not city:
            continue

        mall_id = slugify(name)
        prov_id = slugify(province)
        city_id = slugify(city)

        rdf_content += f'''    <!-- Mall: {escape_xml(name)} -->
    <ex:Mall rdf:about="http://www.example.org/resource/Mall/{mall_id}">
        <ex:namaMall>{escape_xml(name)}</ex:namaMall>
        <ex:alamatMall>{escape_xml(address)}</ex:alamatMall>
        <ex:beradaDiKota rdf:resource="http://www.example.org/resource/Kota/{city_id}"/>
        <ex:beradaDiProvinsi rdf:resource="http://www.example.org/resource/Provinsi/{prov_id}"/>
'''
        if description:
            rdf_content += f'        <ex:deskripsiMall>{escape_xml(description)}</ex:deskripsiMall>\n'
        rdf_content += '    </ex:Mall>\n\n'

    # Generate Store instances
    print("Generating stores...")
    store_count = 0
    for store in stores:
        if not isinstance(store, dict):
            continue
        store_id = store.get('id', '')
        name = store.get('name', '')
        mall_name = store.get('mall_name', '')
        category = store.get('category', 'Other')
        address = store.get('address', '')
        location = store.get('lokasiToko', '')
        rating = store.get('rating', 0)
        phone = store.get('phone', '')
        website = store.get('website', '')
        jam_buka = store.get('jamBuka', '')

        if not store_id or not name or not mall_name:
            continue

        store_uri = slugify(store_id)
        mall_uri = slugify(mall_name)
        cat_uri = slugify(category)

        rdf_content += f'''    <!-- Store: {escape_xml(name)} -->
    <ex:Toko rdf:about="http://www.example.org/resource/Toko/{store_uri}">
        <ex:namaToko>{escape_xml(name)}</ex:namaToko>
        <ex:beradaDiMall rdf:resource="http://www.example.org/resource/Mall/{mall_uri}"/>
        <ex:memilikiKategori rdf:resource="http://www.example.org/resource/Kategori/{cat_uri}"/>
        <ex:kategoriToko>{escape_xml(category)}</ex:kategoriToko>
        <ex:lokasiToko>{escape_xml(location)}</ex:lokasiToko>
'''
        if address:
            rdf_content += f'        <ex:alamatToko>{escape_xml(address)}</ex:alamatToko>\n'
        if rating and float(rating) > 0:
            rdf_content += f'        <ex:ratingToko rdf:datatype="http://www.w3.org/2001/XMLSchema#float">{rating}</ex:ratingToko>\n'
        if phone:
            rdf_content += f'        <ex:nomorTelepon>{escape_xml(phone)}</ex:nomorTelepon>\n'
        if website:
            rdf_content += f'        <ex:website>{escape_xml(website)}</ex:website>\n'
        if jam_buka:
            rdf_content += f'        <ex:jamBuka>{escape_xml(jam_buka)}</ex:jamBuka>\n'

        rdf_content += '    </ex:Toko>\n\n'
        store_count += 1

        if store_count % 100 == 0:
            print(f"  Generated {store_count} stores...")

    # Close RDF
    rdf_content += '</rdf:RDF>\n'

    # Save to file
    with open('data_sumateramall.rdf', 'w', encoding='utf-8') as f:
        f.write(rdf_content)

    print(f"\n{'='*50}")
    print(f"RDF/XML berhasil dibuat!")
    print(f"File: data_sumateramall.rdf")
    print(f"Total stores: {store_count}")
    print(f"{'='*50}")

if __name__ == '__main__':
    generate_rdf_xml()
