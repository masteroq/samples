<?php


/**
 * Ajax functionality
 */

require plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

if (!class_exists('GuzzleHttp\Client')) {
    die('GuzzleHttp\Client class not found');
}

if (!class_exists('Symfony\Component\DomCrawler\Crawler')) {
    die('Symfony\Component\DomCrawler\Crawler class not found');
}

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Apigames_Ajax {

	public function __construct() {
		add_action('wp_ajax_ag_get_slots', [$this, 'get_slots']);
		add_action('wp_ajax_nopriv_ag_get_slots', [$this, 'get_slots']);

		add_action('wp_ajax_ag_get_providers', [$this, 'get_providers']);
		add_action('wp_ajax_nopriv_ag_get_providers', [$this, 'get_providers']);

		add_action('wp_ajax_ag_get_games_by_provider', [$this, 'get_games_by_provider']);
		add_action('wp_ajax_nopriv_ag_get_games_by_provider', [$this, 'get_games_by_provider']);

		add_action('wp_ajax_ag_import_game', [$this, 'import_game']);
		add_action('wp_ajax_nopriv_ag_import_game', [$this, 'import_game']);
		
		add_action('wp_ajax_ag_search_games_by_name', [$this, 'search_games_by_name']);
		add_action('wp_ajax_nopriv_ag_search_games_by_name', [$this, 'search_games_by_name']);

		add_action('wp_ajax_ag_filtering_games', [$this, 'filtering_games']);
		add_action('wp_ajax_nopriv_ag_filtering_games', [$this, 'filtering_games']);
	}

	public function import_game() {
		$game_id = (int)$_POST['game_id'];

		$json_data = file_get_contents(APIGAMES_PLUGIN_DIR . 'admin/json/all_games_data.json');

		$games = json_decode($json_data, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			wp_send_json_error('Помилка при декодуванні JSON: ' . json_last_error_msg());
			exit;
		}

		$filtered_games = array_filter($games, function($game) use ($game_id) {
			return $game['id'] === $game_id;
		});

		$result_game = reset($filtered_games);

		$post_id = wp_insert_post([
			'post_title'   => $result_game['game_title'],
			'post_type'    => 'api-games',
			'post_status'  => 'publish',
			'post_content' => '',
		]);

		if (!is_wp_error($post_id)) {

			set_featured_image($result_game['game_image'], $post_id);

			if (!empty($result_game['provider'])) {
				$provider_term = term_exists($result_game['provider'], 'providers');
				if (!$provider_term) {
					$provider_term = wp_insert_term($result_game['provider'], 'providers');
				}

				if (!is_wp_error($provider_term)) {
					wp_set_object_terms($post_id, (int) $provider_term['term_id'], 'providers');
				}
			}

			update_post_meta($post_id, 'data_frame', $result_game['data_frame']);
			update_post_meta($post_id, 'game_rating', $result_game['game_rating']);
			update_post_meta($post_id, 'data_start', $result_game['data_start']);
			update_post_meta($post_id, 'min_bet', $result_game['min_bet']);
			update_post_meta($post_id, 'max_bet', $result_game['max_bet']);
			update_post_meta($post_id, 'max_payout', $result_game['max_payout']);
			update_post_meta($post_id, 'reels', $result_game['reels_count']);
			update_post_meta($post_id, 'rows', $result_game['rows_count']);
			update_post_meta($post_id, 'lines', $result_game['lines_count']);
			update_post_meta($post_id, 'rtp', $result_game['rtp']);
			update_post_meta($post_id, 'volatility', $result_game['volatility']);
			update_post_meta($post_id, 'platforms', $result_game['platforms']);

		}

		wp_send_json_success($filtered_games);
	
		exit;
	}

	public function get_games_by_provider() {
		$provider = $_POST['provider'];
		
		$json_data = file_get_contents(APIGAMES_PLUGIN_DIR . 'admin/json/all_games_data.json');

		$games = json_decode($json_data, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			wp_send_json_error('Помилка при декодуванні JSON: ' . json_last_error_msg());
			exit;
		}

		$filtered_games = array_filter($games, function($game) use ($provider) {
			return $game['provider'] === $provider;
		});

		$result = '';

		foreach($filtered_games as $game) {
			$check_game = check_if_game_exists($game['game_title']);
			$result .= '<div class="game__card">';
            	$result .= '<div class="game__card-image"><img width="100px" src="' . $game['game_image'] . '"></div>';
            		$result .= '<div class="game__card-info">';
            			$result .= '<div class="game__card-title">'. $game['game_title']. '</div>';
            			$result .= '<div class="game__card-info-secondary">';
							$result .= '<div class="game__card-rating">Rating: '. $game['game_rating'] . '</div>';
							$result .= '<div class="game__card-minbet">Min Bet: ' . $game['min_bet'] . '</div>';
							$result .= '<div class="game__card-maxbet">Max Bet: ' . $game['max_bet'] . '</div>';
						$result .= '</div>';
						$result .= '<div class="game__card-info-secondary">';
							$result .= '<div class="game__card-payout">Max Payout: ' . $game['max_payout'] . '</div>';
							$result .= '<div class="game__card-reels">Reels: ' . $game['reels_count'] . '</div>';
							$result .= '<div class="game__card-maxbet">Rows: ' . $game['rows_count'] . '</div>';
						$result .= '</div>';
					$result .= '</div>';
					$result .= '<div class="game__card-platforms">Platforms: ' . $game['platforms'] . '</div>';
					if($check_game === false) {
						$result .= '<div class="game__card-import-button"><button class="import-game-button" type="button" data-id="' . $game['id'] . '">Import game</button><//div>';
					} else {
						$result .= '<div class="game__card-import-button"><button class="import-game-button disabled-button" type="button" data-id="' . $game['id'] . '" disabled="disabled">Already imported</button><//div>';
					}
					
				$result .= '</div>';
			$result .= '</div>';
		}

		wp_send_json_success(['html' => $result]);
	
		exit;
	}

	public function search_games_by_name() {
		$game_name = $_POST['s'];
		
		$json_data = file_get_contents(APIGAMES_PLUGIN_DIR . 'admin/json/all_games_data.json');

		$games = json_decode($json_data, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			wp_send_json_error('Помилка при декодуванні JSON: ' . json_last_error_msg());
			exit;
		}

		$filtered_games = array_filter($games, function($game) use ($game_name) {
			return stripos($game['game_title'], $game_name) !== false;
		});

		$result = '';

		foreach($filtered_games as $game) {
			$check_game = check_if_game_exists($game['game_title']);
			$result .= '<div class="game__card">';
            	$result .= '<div class="game__card-image"><img width="100px" src="' . $game['game_image'] . '"></div>';
            		$result .= '<div class="game__card-info">';
            			$result .= '<div class="game__card-title">'. $game['game_title']. '</div>';
            			$result .= '<div class="game__card-info-secondary">';
							$result .= '<div class="game__card-rating">Rating: '. $game['game_rating'] . '</div>';
							$result .= '<div class="game__card-minbet">Min Bet: ' . $game['min_bet'] . '</div>';
							$result .= '<div class="game__card-maxbet">Max Bet: ' . $game['max_bet'] . '</div>';
						$result .= '</div>';
						$result .= '<div class="game__card-info-secondary">';
							$result .= '<div class="game__card-payout">Max Payout: ' . $game['max_payout'] . '</div>';
							$result .= '<div class="game__card-reels">Reels: ' . $game['reels_count'] . '</div>';
							$result .= '<div class="game__card-maxbet">Rows: ' . $game['rows_count'] . '</div>';
						$result .= '</div>';
					$result .= '</div>';
					$result .= '<div class="game__card-platforms">Platforms: ' . $game['platforms'] . '</div>';
					if($check_game === false) {
						$result .= '<div class="game__card-import-button"><button class="import-game-button" type="button" data-id="' . $game['id'] . '">Import game</button><//div>';
					} else {
						$result .= '<div class="game__card-import-button"><button class="import-game-button disabled-button" type="button" data-id="' . $game['id'] . '" disabled="disabled">Already imported</button><//div>';
					}
					
				$result .= '</div>';
			$result .= '</div>';
		}

		wp_send_json_success(['html' => $result]);
	
		exit;
	}

	function get_slots() {
		// Add debug message
		error_log('Function get_slots started');
		
		$client = new Client();

		$meta_keys_map = [
			'Дата выхода:' => 'data_start',
			'Жанр:' => 'genre',
			'Минимальная ставка:' => 'min_bet',
			'Максимальная ставка:' => 'max_bet',
			'Максимальная выплата:' => 'max_payout',
			'Количество барабанов:' => 'reels_count',
			'Количество рядов:' => 'rows_count',
			'Количество линий:' => 'lines_count',
			'RTP (%):' => 'rtp',
			'Волатильность:' => 'volatility',
			'Платформы:' => 'platforms'
		];

		$dataFrames = [];
	
		for ($page = 1; $page <= 56; $page++) {
			$url = "https://casino.ru/igrovye-avtomaty-besplatno/page/$page/";
			error_log('Fetching URL: ' . $url);
			try {
				$response = $client->request('GET', $url);
				$html = $response->getBody()->getContents();
				$crawler = new Crawler($html);
	
				$links = $crawler->filter('.short__brand--thumb')->each(function (Crawler $node) {
					return $node->attr('href');
				});
	
				foreach ($links as $link) {
					error_log('Processing link: ' . $link);
					$response = $client->request('GET', $link);
					$html = $response->getBody()->getContents();
					$crawler = new Crawler($html);
	
					$dataFrame = $crawler->filter('.box__game-main')->attr('data-frame');
					$gameData = [];
	
					if ($dataFrame) {
						$gameData['data_frame'] = $dataFrame;
					}

					$gameTitle = $crawler->filter('h1.game-title span')->text();
					if ($gameTitle) {
						$gameData['game_title'] = $gameTitle;
					}

					$gameImage = $crawler->filter('.box__game-main img')->attr('src');
					if ($gameImage) {
						$gameData['game_image'] = $gameImage;
					}

					$gameRating = $crawler->filter('.game-rating span.screen-reader-text')->text();
					if ($gameRating) {
						$gameData['game_rating'] = $gameRating;
					}
					// Извлечение других данных
					$gameProps = $crawler->filter('.list__dotted.game__props.game__props li')->each(function (Crawler $node) use ($meta_keys_map) {
						$label = trim($node->filter('.list-label')->text());
						$value = trim($node->filter('.list-value')->text());
						return [isset($meta_keys_map[$label]) ? $meta_keys_map[$label] : $label => $value];
					});
	
					foreach ($gameProps as $prop) {
						$gameData[key($prop)] = current($prop);
					}

					$provider = $crawler->filter('.label-awards.brands a span')->text();
					if ($provider) {
						$gameData['provider'] = $provider;
					}
	
					if (!empty($gameData)) {
						$dataFrames[] = $gameData;
						//break;
					}
				}
			} catch (Exception $e) {
				error_log('Error fetching URL: ' . $url . ' - ' . $e->getMessage());
			}

			

			// foreach ($dataFrames as $gameData) {
			// 	if (!empty($gameData['game_title'])) {
			// 		$post_id = wp_insert_post([
			// 			'post_title'   => $gameData['game_title'],
			// 			'post_type'    => 'api-games',
			// 			'post_status'  => 'publish',
			// 			'post_content' => '',
			// 		]);
		
			// 		if (!is_wp_error($post_id)) {
			// 			foreach ($gameData as $meta_key => $meta_value) {
			// 				if ($meta_key !== 'game_title' && $meta_key !== 'provider') {
			// 					$new_meta_key = isset($meta_keys_map[$meta_key]) ? $meta_keys_map[$meta_key] : $meta_key;
			// 					update_post_meta($post_id, $new_meta_key, $meta_value);
			// 				}
			// 			}
			// 			if (!empty($gameData['game_image'])) {
			// 				update_post_meta($post_id, 'game_image', $gameData['game_image']);
			// 			}
			// 			if (!empty($gameData['provider'])) {
			// 				$provider_term = term_exists($gameData['provider'], 'providers');
			// 				if (!$provider_term) {
			// 					$provider_term = wp_insert_term($gameData['provider'], 'providers');
			// 				}
		
			// 				if (!is_wp_error($provider_term)) {
			// 					wp_set_object_terms($post_id, (int) $provider_term['term_id'], 'providers');
			// 				}
			// 			}
			// 		} else {
			// 			error_log('Error creating post: ' . $post_id->get_error_message());
			// 		}
			// 	}
			// }
		}
		$json_data = json_encode($dataFrames, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$upload_dir = wp_upload_dir();
		$file_path = $upload_dir['path'] . '/game_data.json';
		file_put_contents($file_path, $json_data);

		wp_send_json_success(['data' => $dataFrames, 'json_file' => $file_path]);
	}

	function get_providers() {
		// Добавьте отладочные сообщения
		error_log('Function get_slots started');
		
		$client = new Client();
		$dataProviders = [];
	
		//for ($page = 1; $page <= 56; $page++) {
			$url = "https://casino.ru/proizvoditeli-slotov/";
			error_log('Fetching URL: ' . $url);
			try {
				$response = $client->request('GET', $url);
				$html = $response->getBody()->getContents();
				$crawler = new Crawler($html);
	
				$links = $crawler->filter('.provider__info .provider__name')->each(function (Crawler $node) {
					return $node->attr('href');
				});
	
				foreach ($links as $link) {
					error_log('Processing link: ' . $link);
					$response = $client->request('GET', $link);
					$html = $response->getBody()->getContents();
					$crawler = new Crawler($html);
	
					// Извлечение текста из h1 span
					$providerTitle = $crawler->filter('.brand-logo img')->attr('alt');
					if ($providerTitle) {
						if (!term_exists($providerTitle, 'providers')) {
							wp_insert_term($providerTitle, 'providers');
							error_log('Added new provider term: ' . $providerTitle);
						} else {
							error_log('Provider term already exists: ' . $providerTitle);
						}
						$providerData['provider_title'] = $providerTitle;
					}

					$providerImage = $crawler->filter('.brand-logo img')->attr('src');
					if ($providerImage) {
						$providerData['provider_image'] = $providerImage;
					}
	
					if (!empty($providerData)) {
						$dataProviders[] = $providerData;
						break;
					}
				}
			} catch (Exception $e) {
				error_log('Error fetching URL: ' . $url . ' - ' . $e->getMessage());
			}

		//}
	
		wp_send_json_success($dataProviders);
	}

	function filtering_games() {
		$objectData = json_decode(html_entity_decode(stripslashes($_POST['objectDataGames'])));

		$games_args = array(
			'post_type' => 'api-games',
			'posts_per_page' => 12,
			'post_parent' => 0,
			'paged' => $objectData->paged,
			'meta_query' => array(
				'relation' => 'AND',
			),
			'tax_query' => array(
				'relation' => 'AND',
			),
		);

		if ($objectData->sorting != NULL) {
			if ($objectData->sorting == 'rating') {
				$games_args['orderby'] = 'meta_value_num';
				$games_args['meta_key'] = 'casino_rating';
			} elseif ($objectData->sorting == 'date') {
				$games_args['orderby'] = 'date';
			}
		}

		if ($objectData->ordering != NULL) {
			if ($games_args->ordering == 'desc') {
				$games_args['order'] = 'DESC';
			} elseif ($objectData->ordering == 'asc') {
				$games_args['order'] = 'ASC';
			}
		}

		if ($objectData->provider != NULL) {
			$games_args['tax_query'][] = array(
				'taxonomy' => 'providers',
				'field' => 'slug',
				'terms' => $objectData->provider,
			);
		}

		$games = new WP_Query($games_args);

		$result = '';

		if ($games->have_posts()) :
			while ($games->have_posts()) : $games->the_post();
				include(APIGAMES_PLUGIN_DIR. '/templates/single-game.php');
			endwhile;
		endif;

		echo $result;
		exit;
	}

	private function map( array $posts ) {
		$mapped = [];
		if( $posts ) {
			foreach ($posts as $p ) {
				$img = apigames_img_url($p->ID);
				if ( ! $img ) {
					$img = APIGAMES_PLUGIN_URL . 'public/img/no-image-available.png';
				}
				$terms = get_the_terms($p->ID, 'sl-provider' );
				$mapped[] = [
					'id'    => $p->ID,
					'title'  => $p->post_title,
					'url'   => get_permalink($p->ID),
					'img' => $img,
					'provider_url' => '?sl-provider='. $terms[0]->slug,
					'provider_name' => $terms[0]->name
				];
			}
		}
		return $mapped;
	}
	private function mapProviders( array $providers ) {
		$mapped = [];

		foreach ($providers as $p ) {
			$img = provider_img_url($p->term_id);
			if ( ! $img ) {
				$img = APIGAMES_PLUGIN_URL . 'public/img/no-image-available.png';
			}
			$mapped[] = [
				'id'    => $p->term_id,
				'title'  => $p->name,
				'url'   => '?sl-provider='. $p->slug,
				'img' => $img,
				'count' => $p->count
			];
		}

		return $mapped;
	}
}

new Apigames_Ajax();