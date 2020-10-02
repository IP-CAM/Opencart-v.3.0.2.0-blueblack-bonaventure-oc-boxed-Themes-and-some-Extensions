<?php
class ModelAccountReturn extends Model {

				public function getReturnReason($return_reason_id) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "return_reason WHERE return_reason_id = '" . (int)$return_reason_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					return $query->row;
				}
				
	public function addReturn($data) {

				/* MailOnReturn */
				$this->load->model('localisation/return_reason');

				$return_email_subject = "New Product Return Request - " . $this->db->escape($data['firstname']) . " " . $this->db->escape($data['lastname']);
				$return_email_text = "A new product return request has been made in your store.\n\n";
				$return_email_text .= "Customer: " . $this->db->escape($data['firstname']) . " " . $this->db->escape($data['lastname']) . "\n";

				if (!empty($data['email'])) {
					$return_email_text .= 'Email: ' . $this->db->escape($data['email']) . "\n";
				}

				if (!empty($data['telephone'])) {
					$return_email_text .= 'Phone: ' . $this->db->escape($data['telephone']) . "\n\n";
				}

				$return_email_text .= 'Order ID: ' . $data['order_id'] . "\n";
				$return_email_text .= 'Date Ordered: ' . $data['date_ordered'] . "\n";
				$return_email_text .= 'Product: ' . $this->db->escape($data['product']) . "\n";
				$return_email_text .= 'Product Model: ' . $this->db->escape($data['model']) . "\n\n";

				$return_reason_info = $this->getReturnReason($data['return_reason_id']);

				if (!empty($return_reason_info['name'])) {
					$return_email_text .= 'Return Reason: ' . $this->db->escape($return_reason_info['name']) . "\n";
				}

				if (!empty($data['comment'])) {
					$return_email_text .= 'Comment: ' . $this->db->escape($data['comment']) . "\n\n";
				}

				$return_email_text .= 'You can review the order return from your OpenCart administration';

				$mail = new Mail($this->config->get('config_mail_engine'));
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
				$mail->setTo($this->config->get('config_email'));
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($this->config->get('config_name'));
				$mail->setSubject(html_entity_decode($return_email_subject, ENT_QUOTES, 'UTF-8'));
				$mail->setText(html_entity_decode($return_email_text, ENT_QUOTES, 'UTF-8'));
				$mail->send();
				/* End MailOnReturn */
			
		$this->db->query("INSERT INTO `" . DB_PREFIX . "return` SET order_id = '" . (int)$data['order_id'] . "', customer_id = '" . (int)$this->customer->getId() . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', product = '" . $this->db->escape($data['product']) . "', model = '" . $this->db->escape($data['model']) . "', quantity = '" . (int)$data['quantity'] . "', opened = '" . (int)$data['opened'] . "', return_reason_id = '" . (int)$data['return_reason_id'] . "', return_status_id = '" . (int)$this->config->get('config_return_status_id') . "', comment = '" . $this->db->escape($data['comment']) . "', date_ordered = '" . $this->db->escape($data['date_ordered']) . "', date_added = NOW(), date_modified = NOW()");


		if ( class_exists( 'Advertikon\Adk_Gdpr\Term', true ) ) {
			$t = new \Advertikon\Adk_Gdpr\Term();
			$t->add_return_acceptance( $this->db->getLastId(), $data );
		}
			
		return $this->db->getLastId();
	}

	public function getReturn($return_id) {
		$query = $this->db->query("SELECT r.return_id, r.order_id, r.firstname, r.lastname, r.email, r.telephone, r.product, r.model, r.quantity, r.opened, (SELECT rr.name FROM " . DB_PREFIX . "return_reason rr WHERE rr.return_reason_id = r.return_reason_id AND rr.language_id = '" . (int)$this->config->get('config_language_id') . "') AS reason, (SELECT ra.name FROM " . DB_PREFIX . "return_action ra WHERE ra.return_action_id = r.return_action_id AND ra.language_id = '" . (int)$this->config->get('config_language_id') . "') AS action, (SELECT rs.name FROM " . DB_PREFIX . "return_status rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, r.comment, r.date_ordered, r.date_added, r.date_modified FROM `" . DB_PREFIX . "return` r WHERE r.return_id = '" . (int)$return_id . "' AND r.customer_id = '" . $this->customer->getId() . "'");

		return $query->row;
	}

	public function getReturns($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT r.return_id, r.order_id, r.firstname, r.lastname, rs.name as status, r.date_added FROM `" . DB_PREFIX . "return` r LEFT JOIN " . DB_PREFIX . "return_status rs ON (r.return_status_id = rs.return_status_id) WHERE r.customer_id = '" . $this->customer->getId() . "' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY r.return_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalReturns() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return`WHERE customer_id = '" . $this->customer->getId() . "'");

		return $query->row['total'];
	}

	public function getReturnHistories($return_id) {
		$query = $this->db->query("SELECT rh.date_added, rs.name AS status, rh.comment FROM " . DB_PREFIX . "return_history rh LEFT JOIN " . DB_PREFIX . "return_status rs ON rh.return_status_id = rs.return_status_id WHERE rh.return_id = '" . (int)$return_id . "' AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY rh.date_added ASC");

		return $query->rows;
	}
}
