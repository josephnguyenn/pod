<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Names_Numbers') ) {

	class FPD_Names_Numbers {

		public static function display_names_numbers_items( $views ) {

			echo '<div class="fpd-table-item-names-numbers">';

			if( is_array($views) ) {

				foreach($views as $view) {

					if( isset($view['names_numbers']) ) {

						$names_numbers = $view['names_numbers'];
						foreach($names_numbers as $name_number) {

							$nn_line = array();

							if( isset($name_number['name']) )
								$nn_line[] = $name_number['name'];

							if( isset($name_number['number']) )
								$nn_line[] = $name_number['number'];

							if( isset($name_number['select']) )
								$nn_line[] = $name_number['select'];

							echo '<div>'. implode(' / ', $nn_line) .'</div>';

			    		}
			    	}
			    }

		    }

		    echo '</div>';
		    ?>
		    <style type="text/css">
			    .fpd-table-item-names-numbers {
				    font-size: 12px;
			    }

		    </style>
		    <?php

		}

	}

}

?>