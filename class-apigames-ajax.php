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

		add_action('wp_ajax_ag_import_game', [$this, 'import_game']);
		add_action('wp_ajax_nopriv_ag_import_game', [$this, 'import_game']);

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

}

new Apigames_Ajax();
