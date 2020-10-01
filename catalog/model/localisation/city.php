<?php
	class ModelLocalisationCity extends Model {
		public function getCities($zone_id) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "city WHERE zone_id = '" . (int)$zone_id . "' AND status = '1'");
			
			return $query->rows;
		}
		
	}
