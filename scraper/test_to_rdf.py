import unittest

from rdflib import Graph, URIRef
from rdflib.namespace import RDF, XSD

from to_rdf import SCHEMA, add_opening_hours, category_for, parse_opening_hours


class RdfConversionTest(unittest.TestCase):
    def test_opening_hours_expand_weekdays_and_multiple_periods(self):
        hours = parse_opening_hours("Mo-Fr 10:00-14:00,15:00-22:00; Sa-Su 10:00-23:00")

        self.assertEqual(len(hours), 12)
        self.assertIn(("Monday", "10:00:00", "14:00:00"), hours)
        self.assertIn(("Friday", "15:00:00", "22:00:00"), hours)
        self.assertIn(("Sunday", "10:00:00", "23:00:00"), hours)

    def test_all_day_opening_hours_cover_each_weekday(self):
        self.assertEqual(len(parse_opening_hours("24/7")), 7)

    def test_overnight_hours_continue_into_the_next_day(self):
        hours = parse_opening_hours("Mo 18:00-02:00")

        self.assertIn(("Monday", "18:00:00", "23:59:59"), hours)
        self.assertIn(("Tuesday", "00:00:00", "02:00:00"), hours)

    def test_opening_hours_are_written_as_schema_opening_specifications(self):
        graph = Graph()
        store = URIRef("http://sumateramall.org/resource/Store/test")
        add_opening_hours(graph, store, "Mo-Fr 10:00-22:00")
        specifications = list(graph.objects(store, SCHEMA.openingHoursSpecification))

        self.assertEqual(len(specifications), 5)
        self.assertTrue(all((specification, RDF.type, SCHEMA.OpeningHoursSpecification) in graph for specification in specifications))
        opening_times = [value for specification in specifications for value in graph.objects(specification, SCHEMA.opens)]
        closing_times = [value for specification in specifications for value in graph.objects(specification, SCHEMA.closes)]
        self.assertEqual(str(opening_times[0]), "10:00:00")
        self.assertEqual(opening_times[0].datatype, XSD.time)
        self.assertEqual(str(closing_times[0]), "22:00:00")

    def test_scraped_categories_share_the_defined_rdf_category(self):
        self.assertEqual(category_for("Food & Beverage"), ("fnb", "Food & Beverage"))
        self.assertEqual(category_for("restaurant"), ("fnb", "Food & Beverage"))
        self.assertEqual(category_for("unmapped type")[0], "other")


if __name__ == "__main__":
    unittest.main()
