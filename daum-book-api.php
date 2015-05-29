<?php
/*
Plugin Name: daum book api
Plugin URI: http://parkyong.com
Description: search book and add book information to post
Version: 1.0
Author: Park Yong
Author URI: http://parkyong.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

add_action('admin_menu', 'daum_book_menu');
add_action('admin_init', 'daum_book_register_settings');

add_action( 'save_post', 'daum_book_save_meta_box', 10, 2 );
add_action( 'admin_init', 'daum_book_init' );
add_shortcode( 'daumbook', 'daum_book_shortcode' );


function daum_book_menu() {
	add_options_page( __('Daum Book API Setting Page', 'daum-book'), 
		__('Daum Book API Setting', 'daum-book-plugin'), 'administrator', __FILE__, 'daum_book_setting_page');
}


function daum_book_register_settings () {
	register_setting( 'daum-book-settings-group', 'daum_book_options' );
}

function daum_book_setting_page () {
	$daum_book_options = get_option('daum_book_options');
	$daum_api_key = $daum_book_options['api_key'];
	?>
	<div class="wrap">
		<h2><?php _e('DAUM BOOK API OPTIONS', 'daum-book') ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'daum-book-settings-group'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Enter API Key', 'daum-book'); ?></th>
					<td><input type="text" name="daum_book_options[api_key]" value="<?php echo $daum_api_key; ?>"></td>
				</tr>
			</table>
			<p>If you don't have API Key</p>
			<a href="https://developers.daum.net/services">Click</a>
			
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'daum-book' ); ?>" >
			</p>
		</form>
	</div>
<?php	
}

function daum_book_init() {
	add_meta_box( 'daum-book-meta', __('Daum Book Api', 'daum-book' ), 'daum_book_meta_box', 'post', 'side', 'default' );
}

function daum_book_meta_box ( $post, $box ) {
	$dba_title = get_post_meta( $post->ID, 'dba_title', true );
	$dba_author = get_post_meta( $post->ID, 'dba_author', true );
	$dba_image_src = get_post_meta( $post->ID, 'dba_image_src', true );

	echo '<table>';
	echo '<tr>';
	echo '<td>' . __('Title', 'daum-book') . ':</td><td><input type="text" name="dba_title" value="' . esc_attr($dba_title) . '" size="10" /></td>';
	echo '</tr><tr>';
	echo '</table>';

}

function daum_book_save_meta_box ( $post_id, $post) {

	if( $post->post_type == 'revision'){
		return ;
	}

	if ( isset( $_POST['dba_title'] ) && $_POST['dba_title'] != '')  {
			
			update_post_meta( $post_id, 'dba_title', esc_attr($_POST['dba_title']) );
		
	}

}

function daum_book_shortcode ( $atts, $content = null ) {
	global $post;

	$daum_book_options = get_option('daum_book_options');
	$key = $daum_book_options['api_key'];
	$dba_title = get_post_meta( $post->ID, 'dba_title', true);

	$searchUrl = 'https://apis.daum.net/search/book';

	$title = preg_replace("/\s+/", "", $dba_title);

	$url = sprintf("%s?apikey=%s&q=%s&output=json&result=1&sort=accu", $searchUrl, $key, $title);
	$result = wp_remote_get( $url );
	$data = $result['body'];
	$decode = json_decode($data, true);
	
	$author = $decode['channel']['item'][0]['author_t'] ;
	$pub_nm = $decode['channel']['item'][0]['pub_nm'];
	$pub_date = $decode['channel']['item'][0]['pub_date'];
	$image_src = $decode['channel']['item'][0]['cover_l_url'];
	$link = $decode['channel']['item'][0]['link'];

	?>
	<div class="book_wrap" style="width:100%; overflow: hidden; padding-left:20%;">
		<img class="book_thumbail" src="<?php echo $image_src; ?>" style="float:left; width:20%;">
		<div class="book_desc" style="float: left; padding-left: 10%; padding-top:5px; width:70%;
		font-family:Hanna">
			<h5 class="book_title"><?php echo $dba_title; ?></h5>
			<p style="margin-bottom:5px;"><?php echo '저자: ' . $author; ?></p>
			<p style="margin-bottom:5px;"><?php echo '출판사: ' . $pub_nm; ?></p>
			<p style="margin-bottom:5px;"><?php echo '출판일: ' . $pub_date; ?></p>
		</div>
	</div>
<?php	
}

?>