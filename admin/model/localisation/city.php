<?php
class ModelLocalisationCity extends Model {
	public function addCity($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "city SET status = '" . (int)$data['status'] . "', name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', zone_id = '" . (int)$data['zone_id'] . "'");

		$this->cache->delete('city');
		
		return $this->db->getLastId();
	}

	public function editCity($city_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "city SET status = '" . (int)$data['status'] . "', name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', zone_id = '" . (int)$data['zone_id'] . "' WHERE city_id = '" . (int)$city_id . "'");

		$this->cache->delete('city');
	}

	public function deleteCity($city_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "city WHERE city_id = '" . (int)$city_id . "'");

		$this->cache->delete('city');
	}

	public function getCity($city_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "city WHERE city_id = '" . (int)$city_id . "'");

		return $query->row;
	}

	public function getCities($data = array()) {
		$sql = "SELECT *, z.name, c.name AS zone FROM " . DB_PREFIX . "city z LEFT JOIN " . DB_PREFIX . "zone c ON (z.zone_id = c.zone_id)";

		$sort_data = array(
			'c.name',
			'z.name',
			'z.code'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getCitiesByZoneId($zone_id) {
		$city_data = $this->cache->get('city.' . (int)$zone_id);

		if (!$city_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "city WHERE zone_id = '" . (int)$zone_id . "' AND status = '1' ORDER BY name");

			$city_data = $query->rows;

			$this->cache->set('city.' . (int)$zone_id, $city_data);
		}

		return $city_data;
	}

	public function getTotalCities() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "city");

		return $query->row['total'];
	}

	public function getTotalCitiesByZoneId($zone_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "city WHERE zone_id = '" . (int)$zone_id . "'");

		return $query->row['total'];
	}

	public function insertCityId() {
		$query = $this->db->query("SELECT " . DB_PREFIX . "address.address_id, IFNULL(" . DB_PREFIX . "city.city_id,0) AS city_id FROM " . DB_PREFIX . "address LEFT JOIN " . DB_PREFIX . "city ON " . DB_PREFIX . "address.city=" . DB_PREFIX . "city.name AND " . DB_PREFIX . "address.zone_id=" . DB_PREFIX . "city.zone_id");
		$city_data = $query->rows;
		foreach ($city_data as $city) {
			$this->db->query("UPDATE " . DB_PREFIX . "address SET " . DB_PREFIX . "address.city_id = " . (int)$city['city_id'] . " WHERE " . DB_PREFIX . "address.address_id = " . (int)$city['address_id']);
		}
	}
}