<?php
class CosineSimilarity {
    static public function dot_product($a, $b) {
		$products = array_map(function($a, $b) {
			return $a * $b;
		}, $a, $b);
		return array_reduce($products, function($a, $b) {
			return $a + $b;
		});
	}
	static public function magnitude($point) {
		$squares = array_map(function($x) {
			return pow($x, 2);
		}, $point);
		return sqrt(array_reduce($squares, function($a, $b) {
			return $a + $b;
		}));
	}

	static public function CosSimilarity($a, $b) {
		return self::dot_product($a, $b) / (self::magnitude($a) * self::magnitude($b)); 
	}
    
	static public function CosSimilarityMags($a, $a_magnitude, $b) {
		return self::dot_product($a, $b) / ($a_magnitude * self::magnitude($b)); 
	}
} 
