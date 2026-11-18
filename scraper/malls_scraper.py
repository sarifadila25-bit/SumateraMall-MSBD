import requests
from bs4 import BeautifulSoup
import json
import re
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
}

MALL_DATABASE = [
    # ACEH
    {"name": "Mal Aceh", "city": "Banda Aceh", "province": "Aceh"},
    {"name": "Aceh Mall", "city": "Banda Aceh", "province": "Aceh"},
    {"name": "Lapanga Mall", "city": "Banda Aceh", "province": "Aceh"},

    # SUMATERA UTARA
    {"name": "Sun Plaza", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Centre Point Mall", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Deli Park Mall", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Medan Mall", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Plaza Medan Fair", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Thamrin Plaza", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "SKA Mall", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Hermes Place", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Millennium Centennial Center", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Naga City Mall", "city": "Medan", "province": "Sumatera Utara"},
    {"name": "Pematang Siantar Mall", "city": "Pematang Siantar", "province": "Sumatera Utara"},

    # SUMATERA BARAT
    {"name": "Plaza Andalas", "city": "Padang", "province": "Sumatera Barat"},
    {"name": "SKA Padang", "city": "Padang", "province": "Sumatera Barat"},
    {"name": "Living World Padang", "city": "Padang", "province": "Sumatera Barat"},

    # RIAU
    {"name": "SKA Mall Pekanbaru", "city": "Pekanbaru", "province": "Riau"},
    {"name": "Mall Ciputra Seraya", "city": "Pekanbaru", "province": "Riau"},
    {"name": "Pusat Pasar City Mall", "city": "Pekanbaru", "province": "Riau"},

    # KEPULAUAN RIAU
    {"name": "Nagoya Hill Mall", "city": "Batam", "province": "Kepulauan Riau"},
    {"name": "Batam City Square", "city": "Batam", "province": "Kepulauan Riau"},
    {"name": "Grand Batam Mall", "city": "Batam", "province": "Kepulauan Riau"},
    {"name": "Mega Mall Batam Centre", "city": "Batam", "province": "Kepulauan Riau"},

    # JAMBI
    {"name": "Jambi Town Square", "city": "Jambi", "province": "Jambi"},
    {"name": "Mall Batang Hari", "city": "Jambi", "province": "Jambi"},

    # SUMATERA SELATAN
    {"name": "Palembang Icon", "city": "Palembang", "province": "Sumatera Selatan"},
    {"name": "Palembang Square", "city": "Palembang", "province": "Sumatera Selatan"},
    {"name": "Palembang Indah Mall", "city": "Palembang", "province": "Sumatera Selatan"},
    {"name": "OPI Mall", "city": "Palembang", "province": "Sumatera Selatan"},
    {"name": "Transmart Palembang", "city": "Palembang", "province": "Sumatera Selatan"},

    # BANGKA BELITUNG
    {"name": "Batu Rusa Mall", "city": "Pangkal Pinang", "province": "Bangka Belitung"},
    {"name": "Pangkal Pinang Mall", "city": "Pangkal Pinang", "province": "Bangka Belitung"},

    # BENGKULU
    {"name": "Bengkulu Mall", "city": "Bengkulu", "province": "Bengkulu"},
    {"name": "Rafflesia Mall", "city": "Bengkulu", "province": "Bengkulu"},

    # LAMPUNG
    {"name": "Chandra Mall", "city": "Bandar Lampung", "province": "Lampung"},
    {"name": "Rajabasa Square", "city": "Bandar Lampung", "province": "Lampung"},
    {"name": "Lampung Walk", "city": "Bandar Lampung", "province": "Lampung"},
    {"name": "Transmart Lampung", "city": "Bandar Lampung", "province": "Lampung"},
]

def slugify(text):
    text = text.lower().strip()
    text = re.sub(r'[^\w\s-]', '', text)
    text = re.sub(r'[\s_-]+', '_', text)
    return text

def scrape_wikipedia():
    url = "https://id.wikipedia.org/wiki/Daftar_pusat_perbelanjaan_di_Indonesia"
    print(f"Scraping Wikipedia: {url}")
    response = requests.get(url, headers=HEADERS, timeout=20)
    response.raise_for_status()
    soup = BeautifulSoup(response.text, 'html.parser')

    wiki_data = {}
    for table in soup.find_all('table', {'class': 'wikitable'}):
        headers = [th.get_text().strip().lower() for th in table.find_all('th')]
        if 'provinsi' not in headers:
            continue

        for row in table.find_all('tr')[1:]:
            cells = row.find_all('td')
            if len(cells) < 3:
                continue

            nama = cells[0].get_text().strip()
            kota = cells[1].get_text().strip()
            provinsi = cells[2].get_text().strip()
            keterangan = cells[3].get_text().strip() if len(cells) > 3 else ""

            sumatera_provinces = [
                'aceh', 'sumatera utara', 'sumatera barat', 'riau',
                'kepulauan riau', 'jambi', 'sumatera selatan',
                'bangka belitung', 'bengkulu', 'lampung'
            ]

            if any(p in provinsi.lower() for p in sumatera_provinces):
                key = slugify(nama)
                wiki_data[key] = {
                    "description": keterangan,
                    "city_wiki": kota,
                    "province_wiki": provinsi
                }

    print(f"Ditemukan {len(wiki_data)} mall di Wikipedia")
    return wiki_data

def main():
    wiki_data = scrape_wikipedia()

    for mall in MALL_DATABASE:
        key = slugify(mall["name"])
        if key in wiki_data:
            mall["description"] = wiki_data[key]["description"]
            mall["source"] = "hardcoded+wiki"
        else:
            mall["description"] = ""
            mall["source"] = "hardcoded"
        mall["id"] = key

    with (SCRIPT_DIR / "malls_sumatera.json").open("w", encoding="utf-8") as f:
        json.dump(MALL_DATABASE, f, ensure_ascii=False, indent=2)

    print(f"\nTotal {len(MALL_DATABASE)} mall siap diproses")

    from collections import Counter
    provinces = Counter(m["province"] for m in MALL_DATABASE)
    print("\nDistribusi per provinsi:")
    for prov, count in sorted(provinces.items()):
        print(f"  - {prov}: {count} mall")

if __name__ == "__main__":
    main()