import tempfile
import unittest
from pathlib import Path

from rdflib import Graph, Literal, Namespace, RDF

from merge_dataset import EX, MM, SCHEMA, merge_dataset, normalized_category, parse_source


class DatasetMergeTest(unittest.TestCase):
    def test_category_mapping_handles_dataset_categories_in_indonesian_and_english(self):
        self.assertEqual(normalized_category("F&B, Food, Ayam, Fast Food"), "fnb")
        self.assertEqual(normalized_category("Fashion & Gaya Hidup"), "fashion")
        self.assertEqual(normalized_category("Beauty Health"), "beauty")
        self.assertEqual(normalized_category("Unclassified tenant"), "other")

    def test_known_bad_turtle_entries_are_repaired_only_during_parsing(self):
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / "toko_plaza_medan_fair_fixed.ttl"
            original = '@prefix ex: <http://example.test/> .\nex:store ex:namaToko "N"DREW" ; ex:category "Fashion" .\n'
            source.write_text(original, encoding="utf-8")

            graph, repairs = parse_source(source)

            self.assertIn(
                (Namespace("http://example.test/").store, Namespace("http://example.test/").namaToko, Literal('N"DREW')),
                graph,
            )
            self.assertTrue(repairs)
            self.assertEqual(source.read_text(encoding="utf-8"), original)

    def test_merge_adds_application_vocabulary_for_malls_and_stores(self):
        with tempfile.TemporaryDirectory() as directory:
            source_dir = Path(directory) / "source"
            source_dir.mkdir()
            source_file = source_dir / "mall.ttl"
            source_file.write_text(
                '@prefix ex: <http://www.example.org/mall#> .\n'
                '@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n'
                'ex:Provinsi_aceh ex:namaProvinsi "Aceh" .\n'
                'ex:Mall1 rdf:type ex:Mall ; ex:namaMall "Plaza Aceh" ; ex:kota "Banda Aceh" ; '
                'ex:beradaDiProvinsi ex:Provinsi_aceh ; ex:berlokasiDi "Jalan Contoh" .\n'
                'ex:Toko1 rdf:type ex:Toko ; ex:namaToko "Cafe Contoh" ; ex:beradaDiMall ex:Mall1 ; '
                'ex:kategoriToko "F&B, Cafe" ; ex:lokasiToko "L1" .\n',
                encoding="utf-8",
            )
            output = Path(directory) / "merged.ttl"

            merged = merge_dataset(source_dir, output)

            mall = Namespace("http://sumateramall.org/resource/Mall/").plaza_aceh
            store = next(merged.subjects(SCHEMA.name, Literal("Cafe Contoh", lang="id")))
            self.assertTrue(output.exists())
            self.assertIn((mall, RDF.type, MM.Mall), merged)
            self.assertIn((mall, SCHEMA.address, Literal("Jalan Contoh", lang="id")), merged)
            self.assertIn((store, MM.locatedIn, mall), merged)
            self.assertTrue(list(merged.objects(store, MM.hasCategory)))


if __name__ == "__main__":
    unittest.main()
