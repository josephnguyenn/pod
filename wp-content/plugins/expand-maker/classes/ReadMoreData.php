<?php

class ReadMoreData {

	private $id;

	public function __call($name, $args) {

		$methodPrefix = substr($name, 0, 3);
		$methodProperty = lcfirst(substr($name,3));

		if ($methodPrefix=='get') {
			return $this->$methodProperty;
		}
		else if ($methodPrefix=='set') {
			$this->$methodProperty = $args[0];
		}
	}

	public function getSavedOptions() {

		$data = array();
		$id = $this->getId();

		if(!isset($id)) {
			return $data;
		}
		global $wpdb;

		$getSavedSql = $wpdb->prepare("SELECT * FROM ".sanitize_text_field($wpdb->prefix)."expm_maker WHERE id = %d", $id);
		$result = $wpdb->get_row($getSavedSql, ARRAY_A);

		if(empty($result)) {
			return $data;
		}

		$data['type'] = $result['type'];
		$data['expm-title'] = $result['expm-title'];
		$data['button-width'] = $result['button-width'];
		$data['button-height'] = $result['button-height'];
		$data['animation-duration'] = $result['animation-duration'];
		$options = json_decode($result['options'], true);
		$allSavedOptions = $data+$options;

		return apply_filters('yrmAllSavedOptions', $allSavedOptions);
	}

	public static function params() {

		$horizontalAlign = array(
			"left"=>"Left",
			"center"=>"Center",
			"right"=>"Right"
		);

		$arrowIconAlignment = array(
			"left"=>"Left",
			"right"=>"Right"
		);

		$hoverEffect = array(
			'' => 'No effect',
			'flash' => 'Flash',
			'pulse' => 'Pulse',
			'rubberBand' => 'RubberBand',
			'shake' => 'Shake',
			'tada' => 'Tada',
			'jello' => 'Jello'
		);
		
		$cursor = array(
			'pointer' => 'Pointer',
			'help' => 'Help',
			'context-menu' => 'Context Menu',
			'progress' => 'Progress',
			'wait' => 'Wait',
			'crosshair' => 'Crosshair',
			'cell' => 'Cell',
			'text' => 'Text',
			'vertical-text' => 'Vertical Text',
			'grab' => 'Grab',
			'zoom-in' => 'Zoom-in',
			'not-allowed' => 'Not-allowed'
		);

		$vertical = array(
			"top"=>"Top",
			"bottom"=>"Bottom"
		);

		$googleFonts = array(
			'Arial' => 'Arial',
			'Assistant' => 'Assistant',
			'Diplomata SC' => 'Diplomata SC',
			'flavors'=>'Flavors',
			'Open Sans'=> 'Open Sans',
			'Droid Sans'=>'Droid Sans',
			'Droid Serif'=>'Droid Serif',
			'chewy'=>'Chewy',
			'oswald' => 'Oswald',
			'Dancing Script'=> 'Dancing Script',
			'Merriweather'=>'Merriweather',
			'Roboto Condensed'=>'Roboto Condensed',
			'Oswald'=>'Oswald',
			'PT Sans'=>'PT Sans',
			'Montserrat'=>'Montserrat',
			'ABeeZee' => 'ABeeZee',
			'Abel' => 'Abel',
			'Abhaya Libre' => 'Abhaya+Libre',
			'Abril Fatface' => 'Abril+Fatface',
			'Aclonica' => 'Aclonica',
			'Acme' => 'Acme',
			'Actor' => 'Actor',
			'Adamina' => 'Adamina',
			'Advent Pro' => 'Advent+Pro',
			'Aguafina Script' => 'Aguafina+Script',
			'Akronim' => 'Akronim',
			'Aladin' => 'Aladin',
			'Aldrich' => 'Aldrich',
			'Alef' => 'Alef',
			'Alegreya' => 'Alegreya',
			'Alegreya SC' => 'Alegreya+SC',
			'Alegreya Sans' => 'Alegreya+Sans',
			'Alegreya Sans SC' => 'Alegreya+Sans+SC',
			'Alex Brush' => 'Alex+Brush',
			'Alfa Slab One' => 'Alfa+Slab+One',
			'Alice' => 'Alice',
			'Alike' => 'Alike',
			'Alike Angular' => 'Alike+Angular',
			'Allan' => 'Allan',
			'Allerta' => 'Allerta',
			'Allerta Stencil' => 'Allerta+Stencil',
			'Allura' => 'Allura',
			'Almendra' => 'Almendra',
			'Almendra Display' => 'Almendra+Display',
			'Almendra SC' => 'Almendra+SC',
			'Amarante' => 'Amarante',
			'Amaranth' => 'Amaranth',
			'Amatic SC' => 'Amatic+SC',
			'Amethysta' => 'Amethysta',
			'Amiko' => 'Amiko',
			'Amiri' => 'Amiri',
			'Amita' => 'Amita',
			'Anaheim' => 'Anaheim',
			'Andada' => 'Andada',
			'Andika' => 'Andika',
			'Angkor' => 'Angkor',
			'Annie Use Your Telescope' => 'Annie+Use+Your+Telescope',
			'Anonymous Pro' => 'Anonymous+Pro',
			'Antic' => 'Antic',
			'Antic Didone' => 'Antic+Didone',
			'Antic Slab' => 'Antic+Slab',
			'Anton' => 'Anton',
			'Arapey' => 'Arapey',
			'Arbutus' => 'Arbutus',
			'Arbutus Slab' => 'Arbutus+Slab',
			'Architects Daughter' => 'Architects+Daughter',
			'Archivo' => 'Archivo',
			'Archivo Black' => 'Archivo+Black',
			'Archivo Narrow' => 'Archivo+Narrow',
			'Aref Ruqaa' => 'Aref+Ruqaa',
			'Arima Madurai' => 'Arima+Madurai',
			'Arimo' => 'Arimo',
			'Arizonia' => 'Arizonia',
			'Armata' => 'Armata',
			'Arsenal' => 'Arsenal',
			'Artifika' => 'Artifika',
			'Arvo' => 'Arvo',
			'Arya' => 'Arya',
			'Asap' => 'Asap',
			'Asap Condensed' => 'Asap+Condensed',
			'Asar' => 'Asar',
			'Asset' => 'Asset',
			'Assistant' => 'Assistant',
			'Astloch' => 'Astloch',
			'Asul' => 'Asul',
			'Athiti' => 'Athiti',
			'Atma' => 'Atma',
			'Atomic Age' => 'Atomic+Age',
			'Aubrey' => 'Aubrey',
			'Audiowide' => 'Audiowide',
			'Autour One' => 'Autour+One',
			'Average' => 'Average',
			'Average Sans' => 'Average+Sans',
			'Averia Gruesa Libre' => 'Averia+Gruesa+Libre',
			'Averia Libre' => 'Averia+Libre',
			'Averia Sans Libre' => 'Averia+Sans+Libre',
			'Averia Serif Libre' => 'Averia+Serif+Libre',
			'Bad Script' => 'Bad+Script',
			'Bahiana' => 'Bahiana',
			'Baloo' => 'Baloo',
			'Baloo Bhai' => 'Baloo+Bhai',
			'Baloo Bhaijaan' => 'Baloo+Bhaijaan',
			'Baloo Bhaina' => 'Baloo+Bhaina',
			'Baloo Chettan' => 'Baloo+Chettan',
			'Baloo Da' => 'Baloo+Da',
			'Baloo Paaji' => 'Baloo+Paaji',
			'Baloo Tamma' => 'Baloo+Tamma',
			'Baloo Tammudu' => 'Baloo+Tammudu',
			'Baloo Thambi' => 'Baloo+Thambi',
			'Balthazar' => 'Balthazar',
			'Bangers' => 'Bangers',
			'Barlow' => 'Barlow',
			'Barlow Condensed' => 'Barlow+Condensed',
			'Barlow Semi Condensed' => 'Barlow+Semi+Condensed',
			'Barrio' => 'Barrio',
			'Basic' => 'Basic',
			'Battambang' => 'Battambang',
			'Baumans' => 'Baumans',
			'Bayon' => 'Bayon',
			'Belgrano' => 'Belgrano',
			'Bellefair' => 'Bellefair',
			'Belleza' => 'Belleza',
			'BenchNine' => 'BenchNine',
			'Bentham' => 'Bentham',
			'Berkshire Swash' => 'Berkshire+Swash',
			'Bevan' => 'Bevan',
			'Bigelow Rules' => 'Bigelow+Rules',
			'Bigshot One' => 'Bigshot+One',
			'Bilbo' => 'Bilbo',
			'Bilbo Swash Caps' => 'Bilbo+Swash+Caps',
			'BioRhyme' => 'BioRhyme',
			'BioRhyme Expanded' => 'BioRhyme+Expanded',
			'Biryani' => 'Biryani',
			'Bitter' => 'Bitter',
			'Black Ops One' => 'Black+Ops+One',
			'Bokor' => 'Bokor',
			'Bonbon' => 'Bonbon',
			'Boogaloo' => 'Boogaloo',
			'Bowlby One' => 'Bowlby+One',
			'Bowlby One SC' => 'Bowlby+One+SC',
			'Brawler' => 'Brawler',
			'Bree Serif' => 'Bree+Serif',
			'Bubblegum Sans' => 'Bubblegum+Sans',
			'Bubbler One' => 'Bubbler+One',
			'Buda' => 'Buda',
			'Buenard' => 'Buenard',
			'Bungee' => 'Bungee',
			'Bungee Hairline' => 'Bungee+Hairline',
			'Bungee Inline' => 'Bungee+Inline',
			'Bungee Outline' => 'Bungee+Outline',
			'Bungee Shade' => 'Bungee+Shade',
			'Butcherman' => 'Butcherman',
			'Butterfly Kids' => 'Butterfly+Kids',
			'Cabin' => 'Cabin',
			'Cabin Condensed' => 'Cabin+Condensed',
			'Cabin Sketch' => 'Cabin+Sketch',
			'Caesar Dressing' => 'Caesar+Dressing',
			'Cagliostro' => 'Cagliostro',
			'Cairo' => 'Cairo',
			'Calligraffitti' => 'Calligraffitti',
			'Cambay' => 'Cambay',
			'Cambo' => 'Cambo',
			'Candal' => 'Candal',
			'Cantarell' => 'Cantarell',
			'Cantata One' => 'Cantata+One',
			'Cantora One' => 'Cantora+One',
			'Capriola' => 'Capriola',
			'Cardo' => 'Cardo',
			'Carme' => 'Carme',
			'Carrois Gothic' => 'Carrois+Gothic',
			'Carrois Gothic SC' => 'Carrois+Gothic+SC',
			'Carter One' => 'Carter+One',
			'Catamaran' => 'Catamaran',
			'Caudex' => 'Caudex',
			'Caveat' => 'Caveat',
			'Caveat Brush' => 'Caveat+Brush',
			'Cedarville Cursive' => 'Cedarville+Cursive',
			'Ceviche One' => 'Ceviche+One',
			'Changa' => 'Changa',
			'Changa One' => 'Changa+One',
			'Chango' => 'Chango',
			'Chathura' => 'Chathura',
			'Chau Philomene One' => 'Chau+Philomene+One',
			'Chela One' => 'Chela+One',
			'Chelsea Market' => 'Chelsea+Market',
			'Chenla' => 'Chenla',
			'Cherry Cream Soda' => 'Cherry+Cream+Soda',
			'Cherry Swash' => 'Cherry+Swash',
			'Chewy' => 'Chewy',
			'Chicle' => 'Chicle',
			'Chivo' => 'Chivo',
			'Chonburi' => 'Chonburi',
			'Cinzel' => 'Cinzel',
			'Cinzel Decorative' => 'Cinzel+Decorative',
			'Clicker Script' => 'Clicker+Script',
			'Coda' => 'Coda',
			'Coda Caption' => 'Coda+Caption',
			'Codystar' => 'Codystar',
			'Coiny' => 'Coiny',
			'Combo' => 'Combo',
			'Comfortaa' => 'Comfortaa',
			'Coming Soon' => 'Coming+Soon',
			'Concert One' => 'Concert+One',
			'Condiment' => 'Condiment',
			'Content' => 'Content',
			'Contrail One' => 'Contrail+One',
			'Convergence' => 'Convergence',
			'Cookie' => 'Cookie',
			'Copse' => 'Copse',
			'Corben' => 'Corben',
			'Cormorant' => 'Cormorant',
			'Cormorant Garamond' => 'Cormorant+Garamond',
			'Cormorant Infant' => 'Cormorant+Infant',
			'Cormorant SC' => 'Cormorant+SC',
			'Cormorant Unicase' => 'Cormorant+Unicase',
			'Cormorant Upright' => 'Cormorant+Upright',
			'Courgette' => 'Courgette',
			'Cousine' => 'Cousine',
			'Coustard' => 'Coustard',
			'Covered By Your Grace' => 'Covered+By+Your+Grace',
			'Crafty Girls' => 'Crafty+Girls',
			'Creepster' => 'Creepster',
			'Crete Round' => 'Crete+Round',
			'Crimson Text' => 'Crimson+Text',
			'Croissant One' => 'Croissant+One',
			'Crushed' => 'Crushed',
			'Cuprum' => 'Cuprum',
			'Cutive' => 'Cutive',
			'Cutive Mono' => 'Cutive+Mono',
			'Damion' => 'Damion',
			'Dancing Script' => 'Dancing+Script',
			'Dangrek' => 'Dangrek',
			'David Libre' => 'David+Libre',
			'Dawning of a New Day' => 'Dawning+of+a+New+Day',
			'Days One' => 'Days+One',
			'Dekko' => 'Dekko',
			'Delius' => 'Delius',
			'Delius Swash Caps' => 'Delius+Swash+Caps',
			'Delius Unicase' => 'Delius+Unicase',
			'Della Respira' => 'Della+Respira',
			'Denk One' => 'Denk+One',
			'Devonshire' => 'Devonshire',
			'Dhurjati' => 'Dhurjati',
			'Didact Gothic' => 'Didact+Gothic',
			'Diplomata' => 'Diplomata',
			'Diplomata SC' => 'Diplomata+SC',
			'Domine' => 'Domine',
			'Donegal One' => 'Donegal+One',
			'Doppio One' => 'Doppio+One',
			'Dorsa' => 'Dorsa',
			'Dosis' => 'Dosis',
			'Dr Sugiyama' => 'Dr+Sugiyama',
			'Duru Sans' => 'Duru+Sans',
			'Dynalight' => 'Dynalight',
			'EB Garamond' => 'EB+Garamond',
			'Eagle Lake' => 'Eagle+Lake',
			'Eater' => 'Eater',
			'Economica' => 'Economica',
			'Eczar' => 'Eczar',
			'El Messiri' => 'El+Messiri',
			'Electrolize' => 'Electrolize',
			'Elsie' => 'Elsie',
			'Elsie Swash Caps' => 'Elsie+Swash+Caps',
			'Emblema One' => 'Emblema+One',
			'Emilys Candy' => 'Emilys+Candy',
			'Encode Sans' => 'Encode+Sans',
			'Encode Sans Condensed' => 'Encode+Sans+Condensed',
			'Encode Sans Expanded' => 'Encode+Sans+Expanded',
			'Encode Sans Semi Condensed' => 'Encode+Sans+Semi+Condensed',
			'Encode Sans Semi Expanded' => 'Encode+Sans+Semi+Expanded',
			'Engagement' => 'Engagement',
			'Englebert' => 'Englebert',
			'Enriqueta' => 'Enriqueta',
			'Erica One' => 'Erica+One',
			'Esteban' => 'Esteban',
			'Euphoria Script' => 'Euphoria+Script',
			'Ewert' => 'Ewert',
			'Exo' => 'Exo',
			'Exo 2' => 'Exo+2',
			'Expletus Sans' => 'Expletus+Sans',
			'Fanwood Text' => 'Fanwood+Text',
			'Farsan' => 'Farsan',
			'Fascinate' => 'Fascinate',
			'Fascinate Inline' => 'Fascinate+Inline',
			'Faster One' => 'Faster+One',
			'Fasthand' => 'Fasthand',
			'Fauna One' => 'Fauna+One',
			'Faustina' => 'Faustina',
			'Federant' => 'Federant',
			'Federo' => 'Federo',
			'Felipa' => 'Felipa',
			'Fenix' => 'Fenix',
			'Finger Paint' => 'Finger+Paint',
			'Fira Mono' => 'Fira+Mono',
			'Fira Sans' => 'Fira+Sans',
			'Fira Sans Condensed' => 'Fira+Sans+Condensed',
			'Fira Sans Extra Condensed' => 'Fira+Sans+Extra+Condensed',
			'Fjalla One' => 'Fjalla+One',
			'Fjord One' => 'Fjord+One',
			'Flamenco' => 'Flamenco',
			'Flavors' => 'Flavors',
			'Fondamento' => 'Fondamento',
			'Fontdiner Swanky' => 'Fontdiner+Swanky',
			'Forum' => 'Forum',
			'Francois One' => 'Francois+One',
			'Frank Ruhl Libre' => 'Frank+Ruhl+Libre',
			'Freckle Face' => 'Freckle+Face',
			'Fredericka the Great' => 'Fredericka+the+Great',
			'Fredoka One' => 'Fredoka+One',
			'Freehand' => 'Freehand',
			'Fresca' => 'Fresca',
			'Frijole' => 'Frijole',
			'Fruktur' => 'Fruktur',
			'Fugaz One' => 'Fugaz+One',
			'GFS Didot' => 'GFS+Didot',
			'GFS Neohellenic' => 'GFS+Neohellenic',
			'Gabriela' => 'Gabriela',
			'Gafata' => 'Gafata',
			'Galada' => 'Galada',
			'Galdeano' => 'Galdeano',
			'Galindo' => 'Galindo',
			'Gentium Basic' => 'Gentium+Basic',
			'Gentium Book Basic' => 'Gentium+Book+Basic',
			'Geo' => 'Geo',
			'Geostar' => 'Geostar',
			'Geostar Fill' => 'Geostar+Fill',
			'Germania One' => 'Germania+One',
			'Gidugu' => 'Gidugu',
			'Gilda Display' => 'Gilda+Display',
			'Give You Glory' => 'Give+You+Glory',
			'Glass Antiqua' => 'Glass+Antiqua',
			'Glegoo' => 'Glegoo',
			'Gloria Hallelujah' => 'Gloria+Hallelujah',
			'Goblin One' => 'Goblin+One',
			'Gochi Hand' => 'Gochi+Hand',
			'Gorditas' => 'Gorditas',
			'Goudy Bookletter 1911' => 'Goudy+Bookletter+1911',
			'Graduate' => 'Graduate',
			'Grand Hotel' => 'Grand+Hotel',
			'Gravitas One' => 'Gravitas+One',
			'Great Vibes' => 'Great+Vibes',
			'Griffy' => 'Griffy',
			'Gruppo' => 'Gruppo',
			'Gudea' => 'Gudea',
			'Gurajada' => 'Gurajada',
			'Habibi' => 'Habibi',
			'Halant' => 'Halant',
			'Hammersmith One' => 'Hammersmith+One',
			'Hanalei' => 'Hanalei',
			'Hanalei Fill' => 'Hanalei+Fill',
			'Handlee' => 'Handlee',
			'Hanuman' => 'Hanuman',
			'Happy Monkey' => 'Happy+Monkey',
			'Harmattan' => 'Harmattan',
			'Headland One' => 'Headland+One',
			'Heebo' => 'Heebo',
			'Henny Penny' => 'Henny+Penny',
			'Herr Von Muellerhoff' => 'Herr+Von+Muellerhoff',
			'Hind' => 'Hind',
			'Hind Guntur' => 'Hind+Guntur',
			'Hind Madurai' => 'Hind+Madurai',
			'Hind Siliguri' => 'Hind+Siliguri',
			'Hind Vadodara' => 'Hind+Vadodara',
			'Holtwood One SC' => 'Holtwood+One+SC',
			'Homemade Apple' => 'Homemade+Apple',
			'Homenaje' => 'Homenaje',
			'IM Fell DW Pica' => 'IM+Fell+DW+Pica',
			'IM Fell DW Pica SC' => 'IM+Fell+DW+Pica+SC',
			'IM Fell Double Pica' => 'IM+Fell+Double+Pica',
			'IM Fell Double Pica SC' => 'IM+Fell+Double+Pica+SC',
			'IM Fell English' => 'IM+Fell+English',
			'IM Fell English SC' => 'IM+Fell+English+SC',
			'IM Fell French Canon' => 'IM+Fell+French+Canon',
			'IM Fell French Canon SC' => 'IM+Fell+French+Canon+SC',
			'IM Fell Great Primer' => 'IM+Fell+Great+Primer',
			'IM Fell Great Primer SC' => 'IM+Fell+Great+Primer+SC',
			'Iceberg' => 'Iceberg',
			'Iceland' => 'Iceland',
			'Imprima' => 'Imprima',
			'Inconsolata' => 'Inconsolata',
			'Inder' => 'Inder',
			'Indie Flower' => 'Indie+Flower',
			'Inika' => 'Inika',
			'Inknut Antiqua' => 'Inknut+Antiqua',
			'Irish Grover' => 'Irish+Grover',
			'Istok Web' => 'Istok+Web',
			'Italiana' => 'Italiana',
			'Italianno' => 'Italianno',
			'Itim' => 'Itim',
			'Jacques Francois' => 'Jacques+Francois',
			'Jacques Francois Shadow' => 'Jacques+Francois+Shadow',
			'Jaldi' => 'Jaldi',
			'Jim Nightshade' => 'Jim+Nightshade',
			'Jockey One' => 'Jockey+One',
			'Jolly Lodger' => 'Jolly+Lodger',
			'Jomhuria' => 'Jomhuria',
			'Josefin Sans' => 'Josefin+Sans',
			'Josefin Slab' => 'Josefin+Slab',
			'Joti One' => 'Joti+One',
			'Judson' => 'Judson',
			'Julee' => 'Julee',
			'Julius Sans One' => 'Julius+Sans+One',
			'Junge' => 'Junge',
			'Jura' => 'Jura',
			'Just Another Hand' => 'Just+Another+Hand',
			'Just Me Again Down Here' => 'Just+Me+Again+Down+Here',
			'Kadwa' => 'Kadwa',
			'Kalam' => 'Kalam',
			'Kameron' => 'Kameron',
			'Kanit' => 'Kanit',
			'Kantumruy' => 'Kantumruy',
			'Karla' => 'Karla',
			'Karma' => 'Karma',
			'Katibeh' => 'Katibeh',
			'Kaushan Script' => 'Kaushan+Script',
			'Kavivanar' => 'Kavivanar',
			'Kavoon' => 'Kavoon',
			'Kdam Thmor' => 'Kdam+Thmor',
			'Keania One' => 'Keania+One',
			'Kelly Slab' => 'Kelly+Slab',
			'Kenia' => 'Kenia',
			'Khand' => 'Khand',
			'Khmer' => 'Khmer',
			'Khula' => 'Khula',
			'Kite One' => 'Kite+One',
			'Knewave' => 'Knewave',
			'Kotta One' => 'Kotta+One',
			'Koulen' => 'Koulen',
			'Kranky' => 'Kranky',
			'Kreon' => 'Kreon',
			'Kristi' => 'Kristi',
			'Krona One' => 'Krona+One',
			'Kumar One' => 'Kumar+One',
			'Kumar One Outline' => 'Kumar+One+Outline',
			'Kurale' => 'Kurale',
			'La Belle Aurore' => 'La+Belle+Aurore',
			'Laila' => 'Laila',
			'Lakki Reddy' => 'Lakki+Reddy',
			'Lalezar' => 'Lalezar',
			'Lancelot' => 'Lancelot',
			'Lateef' => 'Lateef',
			'Lato' => 'Lato',
			'League Script' => 'League+Script',
			'Leckerli One' => 'Leckerli+One',
			'Ledger' => 'Ledger',
			'Lekton' => 'Lekton',
			'Lemon' => 'Lemon',
			'Lemonada' => 'Lemonada',
			'Libre Barcode 128' => 'Libre+Barcode+128',
			'Libre Barcode 128 Text' => 'Libre+Barcode+128+Text',
			'Libre Barcode 39' => 'Libre+Barcode+39',
			'Libre Barcode 39 Extended' => 'Libre+Barcode+39+Extended',
			'Libre Barcode 39 Extended Text' => 'Libre+Barcode+39+Extended+Text',
			'Libre Barcode 39 Text' => 'Libre+Barcode+39+Text',
			'Libre Baskerville' => 'Libre+Baskerville',
			'Libre Franklin' => 'Libre+Franklin',
			'Life Savers' => 'Life+Savers',
			'Lilita One' => 'Lilita+One',
			'Lily Script One' => 'Lily+Script+One',
			'Limelight' => 'Limelight',
			'Linden Hill' => 'Linden+Hill',
			'Lobster' => 'Lobster',
			'Lobster Two' => 'Lobster+Two',
			'Londrina Outline' => 'Londrina+Outline',
			'Londrina Shadow' => 'Londrina+Shadow',
			'Londrina Sketch' => 'Londrina+Sketch',
			'Londrina Solid' => 'Londrina+Solid',
			'Lora' => 'Lora',
			'Love Ya Like A Sister' => 'Love+Ya+Like+A+Sister',
			'Loved by the King' => 'Loved+by+the+King',
			'Lovers Quarrel' => 'Lovers+Quarrel',
			'Luckiest Guy' => 'Luckiest+Guy',
			'Lusitana' => 'Lusitana',
			'Lustria' => 'Lustria',
			'Macondo' => 'Macondo',
			'Macondo Swash Caps' => 'Macondo+Swash+Caps',
			'Mada' => 'Mada',
			'Magra' => 'Magra',
			'Maiden Orange' => 'Maiden+Orange',
			'Maitree' => 'Maitree',
			'Mako' => 'Mako',
			'Mallanna' => 'Mallanna',
			'Mandali' => 'Mandali',
			'Manuale' => 'Manuale',
			'Marcellus' => 'Marcellus',
			'Marcellus SC' => 'Marcellus+SC',
			'Marck Script' => 'Marck+Script',
			'Margarine' => 'Margarine',
			'Marko One' => 'Marko+One',
			'Marmelad' => 'Marmelad',
			'Martel' => 'Martel',
			'Martel Sans' => 'Martel+Sans',
			'Marvel' => 'Marvel',
			'Mate' => 'Mate',
			'Mate SC' => 'Mate+SC',
			'Maven Pro' => 'Maven+Pro',
			'McLaren' => 'McLaren',
			'Meddon' => 'Meddon',
			'MedievalSharp' => 'MedievalSharp',
			'Medula One' => 'Medula+One',
			'Meera Inimai' => 'Meera+Inimai',
			'Megrim' => 'Megrim',
			'Meie Script' => 'Meie+Script',
			'Merienda' => 'Merienda',
			'Merienda One' => 'Merienda+One',
			'Merriweather' => 'Merriweather',
			'Merriweather Sans' => 'Merriweather+Sans',
			'Metal' => 'Metal',
			'Metal Mania' => 'Metal+Mania',
			'Metamorphous' => 'Metamorphous',
			'Metrophobic' => 'Metrophobic',
			'Michroma' => 'Michroma',
			'Milonga' => 'Milonga',
			'Miltonian' => 'Miltonian',
			'Miltonian Tattoo' => 'Miltonian+Tattoo',
			'Miniver' => 'Miniver',
			'Miriam Libre' => 'Miriam+Libre',
			'Mirza' => 'Mirza',
			'Miss Fajardose' => 'Miss+Fajardose',
			'Mitr' => 'Mitr',
			'Modak' => 'Modak',
			'Modern Antiqua' => 'Modern+Antiqua',
			'Mogra' => 'Mogra',
			'Molengo' => 'Molengo',
			'Molle' => 'Molle',
			'Monda' => 'Monda',
			'Monofett' => 'Monofett',
			'Monoton' => 'Monoton',
			'Monsieur La Doulaise' => 'Monsieur+La+Doulaise',
			'Montaga' => 'Montaga',
			'Montez' => 'Montez',
			'Montserrat' => 'Montserrat',
			'Montserrat Alternates' => 'Montserrat+Alternates',
			'Montserrat Subrayada' => 'Montserrat+Subrayada',
			'Moul' => 'Moul',
			'Moulpali' => 'Moulpali',
			'Mountains of Christmas' => 'Mountains+of+Christmas',
			'Mouse Memoirs' => 'Mouse+Memoirs',
			'Mr Bedfort' => 'Mr+Bedfort',
			'Mr Dafoe' => 'Mr+Dafoe',
			'Mr De Haviland' => 'Mr+De+Haviland',
			'Mrs Saint Delafield' => 'Mrs+Saint+Delafield',
			'Mrs Sheppards' => 'Mrs+Sheppards',
			'Mukta' => 'Mukta',
			'Mukta Mahee' => 'Mukta+Mahee',
			'Mukta Malar' => 'Mukta+Malar',
			'Mukta Vaani' => 'Mukta+Vaani',
			'Muli' => 'Muli',
			'Mystery Quest' => 'Mystery+Quest',
			'NTR' => 'NTR',
			'Neucha' => 'Neucha',
			'Neuton' => 'Neuton',
			'New Rocker' => 'New+Rocker',
			'News Cycle' => 'News+Cycle',
			'Niconne' => 'Niconne',
			'Nixie One' => 'Nixie+One',
			'Nobile' => 'Nobile',
			'Nokora' => 'Nokora',
			'Norican' => 'Norican',
			'Nosifer' => 'Nosifer',
			'Nothing You Could Do' => 'Nothing+You+Could+Do',
			'Noticia Text' => 'Noticia+Text',
			'Noto Sans' => 'Noto+Sans',
			'Noto Serif' => 'Noto+Serif',
			'Nova Cut' => 'Nova+Cut',
			'Nova Flat' => 'Nova+Flat',
			'Nova Mono' => 'Nova+Mono',
			'Nova Oval' => 'Nova+Oval',
			'Nova Round' => 'Nova+Round',
			'Nova Script' => 'Nova+Script',
			'Nova Slim' => 'Nova+Slim',
			'Nova Square' => 'Nova+Square',
			'Numans' => 'Numans',
			'Nunito' => 'Nunito',
			'Nunito Sans' => 'Nunito+Sans',
			'Odor Mean Chey' => 'Odor+Mean+Chey',
			'Offside' => 'Offside',
			'Old Standard TT' => 'Old+Standard+TT',
			'Oldenburg' => 'Oldenburg',
			'Oleo Script' => 'Oleo+Script',
			'Oleo Script Swash Caps' => 'Oleo+Script+Swash+Caps',
			'Open Sans' => 'Open+Sans',
			'Open Sans Condensed' => 'Open+Sans+Condensed',
			'Oranienbaum' => 'Oranienbaum',
			'Orbitron' => 'Orbitron',
			'Oregano' => 'Oregano',
			'Orienta' => 'Orienta',
			'Original Surfer' => 'Original+Surfer',
			'Oswald' => 'Oswald',
			'Over the Rainbow' => 'Over+the+Rainbow',
			'Overlock' => 'Overlock',
			'Overlock SC' => 'Overlock+SC',
			'Overpass' => 'Overpass',
			'Overpass Mono' => 'Overpass+Mono',
			'Ovo' => 'Ovo',
			'Oxygen' => 'Oxygen',
			'Oxygen Mono' => 'Oxygen+Mono',
			'PT Mono' => 'PT+Mono',
			'PT Sans' => 'PT+Sans',
			'PT Sans Caption' => 'PT+Sans+Caption',
			'PT Sans Narrow' => 'PT+Sans+Narrow',
			'PT Serif' => 'PT+Serif',
			'PT Serif Caption' => 'PT+Serif+Caption',
			'Pacifico' => 'Pacifico',
			'Padauk' => 'Padauk',
			'Palanquin' => 'Palanquin',
			'Palanquin Dark' => 'Palanquin+Dark',
			'Pangolin' => 'Pangolin',
			'Paprika' => 'Paprika',
			'Parisienne' => 'Parisienne',
			'Passero One' => 'Passero+One',
			'Passion One' => 'Passion+One',
			'Pathway Gothic One' => 'Pathway+Gothic+One',
			'Patrick Hand' => 'Patrick+Hand',
			'Patrick Hand SC' => 'Patrick+Hand+SC',
			'Pattaya' => 'Pattaya',
			'Patua One' => 'Patua+One',
			'Pavanam' => 'Pavanam',
			'Paytone One' => 'Paytone+One',
			'Peddana' => 'Peddana',
			'Peralta' => 'Peralta',
			'Permanent Marker' => 'Permanent+Marker',
			'Petit Formal Script' => 'Petit+Formal+Script',
			'Petrona' => 'Petrona',
			'Philosopher' => 'Philosopher',
			'Piedra' => 'Piedra',
			'Pinyon Script' => 'Pinyon+Script',
			'Pirata One' => 'Pirata+One',
			'Plaster' => 'Plaster',
			'Play' => 'Play',
			'Playball' => 'Playball',
			'Playfair Display' => 'Playfair+Display',
			'Playfair Display SC' => 'Playfair+Display+SC',
			'Podkova' => 'Podkova',
			'Poiret One' => 'Poiret+One',
			'Poller One' => 'Poller+One',
			'Poly' => 'Poly',
			'Pompiere' => 'Pompiere',
			'Pontano Sans' => 'Pontano+Sans',
			'Poppins' => 'Poppins',
			'Port Lligat Sans' => 'Port+Lligat+Sans',
			'Port Lligat Slab' => 'Port+Lligat+Slab',
			'Pragati Narrow' => 'Pragati+Narrow',
			'Prata' => 'Prata',
			'Preahvihear' => 'Preahvihear',
			'Press Start 2P' => 'Press+Start+2P',
			'Pridi' => 'Pridi',
			'Princess Sofia' => 'Princess+Sofia',
			'Prociono' => 'Prociono',
			'Prompt' => 'Prompt',
			'Prosto One' => 'Prosto+One',
			'Proza Libre' => 'Proza+Libre',
			'Puritan' => 'Puritan',
			'Purple Purse' => 'Purple+Purse',
			'Quando' => 'Quando',
			'Quantico' => 'Quantico',
			'Quattrocento' => 'Quattrocento',
			'Quattrocento Sans' => 'Quattrocento+Sans',
			'Questrial' => 'Questrial',
			'Quicksand' => 'Quicksand',
			'Quintessential' => 'Quintessential',
			'Qwigley' => 'Qwigley',
			'Racing Sans One' => 'Racing+Sans+One',
			'Radley' => 'Radley',
			'Rajdhani' => 'Rajdhani',
			'Rakkas' => 'Rakkas',
			'Raleway' => 'Raleway',
			'Raleway Dots' => 'Raleway+Dots',
			'Ramabhadra' => 'Ramabhadra',
			'Ramaraja' => 'Ramaraja',
			'Rambla' => 'Rambla',
			'Rammetto One' => 'Rammetto+One',
			'Ranchers' => 'Ranchers',
			'Rancho' => 'Rancho',
			'Ranga' => 'Ranga',
			'Rasa' => 'Rasa',
			'Rationale' => 'Rationale',
			'Ravi Prakash' => 'Ravi+Prakash',
			'Redressed' => 'Redressed',
			'Reem Kufi' => 'Reem+Kufi',
			'Reenie Beanie' => 'Reenie+Beanie',
			'Revalia' => 'Revalia',
			'Rhodium Libre' => 'Rhodium+Libre',
			'Ribeye' => 'Ribeye',
			'Ribeye Marrow' => 'Ribeye+Marrow',
			'Righteous' => 'Righteous',
			'Risque' => 'Risque',
			'Roboto' => 'Roboto',
			'Roboto Condensed' => 'Roboto+Condensed',
			'Roboto Mono' => 'Roboto+Mono',
			'Roboto Slab' => 'Roboto+Slab',
			'Rochester' => 'Rochester',
			'Rock Salt' => 'Rock+Salt',
			'Rokkitt' => 'Rokkitt',
			'Romanesco' => 'Romanesco',
			'Ropa Sans' => 'Ropa+Sans',
			'Rosario' => 'Rosario',
			'Rosarivo' => 'Rosarivo',
			'Rouge Script' => 'Rouge+Script',
			'Rozha One' => 'Rozha+One',
			'Rubik' => 'Rubik',
			'Rubik Mono One' => 'Rubik+Mono+One',
			'Ruda' => 'Ruda',
			'Rufina' => 'Rufina',
			'Ruge Boogie' => 'Ruge+Boogie',
			'Ruluko' => 'Ruluko',
			'Rum Raisin' => 'Rum+Raisin',
			'Ruslan Display' => 'Ruslan+Display',
			'Russo One' => 'Russo+One',
			'Ruthie' => 'Ruthie',
			'Rye' => 'Rye',
			'Sacramento' => 'Sacramento',
			'Sahitya' => 'Sahitya',
			'Sail' => 'Sail',
			'Saira' => 'Saira',
			'Saira Condensed' => 'Saira+Condensed',
			'Saira Extra Condensed' => 'Saira+Extra+Condensed',
			'Saira Semi Condensed' => 'Saira+Semi+Condensed',
			'Salsa' => 'Salsa',
			'Sanchez' => 'Sanchez',
			'Sancreek' => 'Sancreek',
			'Sansita' => 'Sansita',
			'Sarala' => 'Sarala',
			'Sarina' => 'Sarina',
			'Sarpanch' => 'Sarpanch',
			'Satisfy' => 'Satisfy',
			'Scada' => 'Scada',
			'Scheherazade' => 'Scheherazade',
			'Schoolbell' => 'Schoolbell',
			'Scope One' => 'Scope+One',
			'Seaweed Script' => 'Seaweed+Script',
			'Secular One' => 'Secular+One',
			'Sedgwick Ave' => 'Sedgwick+Ave',
			'Sedgwick Ave Display' => 'Sedgwick+Ave+Display',
			'Sevillana' => 'Sevillana',
			'Seymour One' => 'Seymour+One',
			'Shadows Into Light' => 'Shadows+Into+Light',
			'Shadows Into Light Two' => 'Shadows+Into+Light+Two',
			'Shanti' => 'Shanti',
			'Share' => 'Share',
			'Share Tech' => 'Share+Tech',
			'Share Tech Mono' => 'Share+Tech+Mono',
			'Shojumaru' => 'Shojumaru',
			'Short Stack' => 'Short+Stack',
			'Shrikhand' => 'Shrikhand',
			'Siemreap' => 'Siemreap',
			'Sigmar One' => 'Sigmar+One',
			'Signika' => 'Signika',
			'Signika Negative' => 'Signika+Negative',
			'Simonetta' => 'Simonetta',
			'Sintony' => 'Sintony',
			'Sirin Stencil' => 'Sirin+Stencil',
			'Six Caps' => 'Six+Caps',
			'Skranji' => 'Skranji',
			'Slabo 13px' => 'Slabo+13px',
			'Slabo 27px' => 'Slabo+27px',
			'Slackey' => 'Slackey',
			'Smokum' => 'Smokum',
			'Smythe' => 'Smythe',
			'Sniglet' => 'Sniglet',
			'Snippet' => 'Snippet',
			'Snowburst One' => 'Snowburst+One',
			'Sofadi One' => 'Sofadi+One',
			'Sofia' => 'Sofia',
			'Sonsie One' => 'Sonsie+One',
			'Sorts Mill Goudy' => 'Sorts+Mill+Goudy',
			'Source Code Pro' => 'Source+Code+Pro',
			'Source Sans Pro' => 'Source+Sans+Pro',
			'Source Serif Pro' => 'Source+Serif+Pro',
			'Space Mono' => 'Space+Mono',
			'Special Elite' => 'Special+Elite',
			'Spectral' => 'Spectral',
			'Spectral SC' => 'Spectral+SC',
			'Spicy Rice' => 'Spicy+Rice',
			'Spinnaker' => 'Spinnaker',
			'Spirax' => 'Spirax',
			'Squada One' => 'Squada+One',
			'Sree Krushnadevaraya' => 'Sree+Krushnadevaraya',
			'Sriracha' => 'Sriracha',
			'Stalemate' => 'Stalemate',
			'Stalinist One' => 'Stalinist+One',
			'Stardos Stencil' => 'Stardos+Stencil',
			'Stint Ultra Condensed' => 'Stint+Ultra+Condensed',
			'Stint Ultra Expanded' => 'Stint+Ultra+Expanded',
			'Stoke' => 'Stoke',
			'Steagal' => 'Steagal',
			'Strait' => 'Strait',
			'Sue Ellen Francisco' => 'Sue+Ellen+Francisco',
			'Suez One' => 'Suez+One',
			'Sumana' => 'Sumana',
			'Sunshiney' => 'Sunshiney',
			'Supermercado One' => 'Supermercado+One',
			'Sura' => 'Sura',
			'Suranna' => 'Suranna',
			'Suravaram' => 'Suravaram',
			'Suwannaphum' => 'Suwannaphum',
			'Swanky and Moo Moo' => 'Swanky+and+Moo+Moo',
			'Syncopate' => 'Syncopate',
			'Tangerine' => 'Tangerine',
			'Taprom' => 'Taprom',
			'Tauri' => 'Tauri',
			'Taviraj' => 'Taviraj',
			'Teko' => 'Teko',
			'Telex' => 'Telex',
			'Tenali Ramakrishna' => 'Tenali+Ramakrishna',
			'Tenor Sans' => 'Tenor+Sans',
			'Text Me One' => 'Text+Me+One',
			'The Girl Next Door' => 'The+Girl+Next+Door',
			'Tienne' => 'Tienne',
			'Tillana' => 'Tillana',
			'Timmana' => 'Timmana',
			'Tinos' => 'Tinos',
			'Titan One' => 'Titan+One',
			'Titillium Web' => 'Titillium+Web',
			'Trade Winds' => 'Trade+Winds',
			'Trirong' => 'Trirong',
			'Trocchi' => 'Trocchi',
			'Trochut' => 'Trochut',
			'Trykker' => 'Trykker',
			'Tulpen One' => 'Tulpen+One',
			'Ubuntu' => 'Ubuntu',
			'Ubuntu Condensed' => 'Ubuntu+Condensed',
			'Ubuntu Mono' => 'Ubuntu+Mono',
			'Ultra' => 'Ultra',
			'Uncial Antiqua' => 'Uncial+Antiqua',
			'Underdog' => 'Underdog',
			'Unica One' => 'Unica+One',
			'UnifrakturCook' => 'UnifrakturCook',
			'UnifrakturMaguntia' => 'UnifrakturMaguntia',
			'Unkempt' => 'Unkempt',
			'Unlock' => 'Unlock',
			'Unna' => 'Unna',
			'VT323' => 'VT323',
			'Vampiro One' => 'Vampiro+One',
			'Varela' => 'Varela',
			'Varela Round' => 'Varela+Round',
			'Vast Shadow' => 'Vast+Shadow',
			'Vesper Libre' => 'Vesper+Libre',
			'Vibur' => 'Vibur',
			'Vidaloka' => 'Vidaloka',
			'Viga' => 'Viga',
			'Voces' => 'Voces',
			'Volkhov' => 'Volkhov',
			'Vollkorn' => 'Vollkorn',
			'Vollkorn SC' => 'Vollkorn+SC',
			'Voltaire' => 'Voltaire',
			'Waiting for the Sunrise' => 'Waiting+for+the+Sunrise',
			'Wallpoet' => 'Wallpoet',
			'Walter Turncoat' => 'Walter+Turncoat',
			'Warnes' => 'Warnes',
			'Wellfleet' => 'Wellfleet',
			'Wendy One' => 'Wendy+One',
			'Wire One' => 'Wire+One',
			'Work Sans' => 'Work+Sans',
			'Yanone Kaffeesatz' => 'Yanone+Kaffeesatz',
			'Yantramanav' => 'Yantramanav',
			'Yatra One' => 'Yatra+One',
			'Yellowtail' => 'Yellowtail',
			'Yeseva One' => 'Yeseva+One',
			'Yesteryear' => 'Yesteryear',
			'Yrsa' => 'Yrsa',
			'Zeyada' => 'Zeyada',
			'Zilla Slab' => 'Zilla+Slab',
			'Zilla Slab Highlight' => 'Zilla+Slab+Highlight',
			'customFont' => 'Custom font'
		);

		$btnFontWeight = array(
			'normal' => 'Normal',
			'bold' => 'Bold',
			'bolder' => 'Bolder',
			'900' => '900',
			'800' => '800',
			'700' => '700',
			'600' => '600',
			'500' => '500',
			'400' => '400',
			'300' => '300',
			'200' => '200',
			'100' => '100',
			'initial' => 'Initial',
			'inherit' => 'Inherit',
		);

		$easings = array(
			'linear' => 'Linear',
			'swing' => 'Swing',
			'easeInQuad' => 'Ease In Quad',
			'easeOutQuad' => 'Ease Out Quad',
			'easeInOutQuad' => 'Ease In Out Quad',
			'easeInCubic' => 'Ease In Cubic',
			'easeOutCubic' => 'Ease Out Cubic',
			'easeInOutCubic' => 'Ease In Out Cubic',
			'easeInQuart' => 'Ease In Quart',
			'easeOutQuart' => 'Ease Out Quart',
			'easeInOutQuart' => 'Ease In Out Quart',
			'easeInQuint' => 'Ease In Quint',
			'easeOutQuint' => 'Ease Out Quint',
			'easeInOutQuint' => 'Ease In Out Quint',
			'easeInExpo' => 'Ease In Expo',
			'easeOutExpo' => 'Ease Out Expo',
			'easeInOutExpo' => 'Ease In Out Expo',
			'easeInSine' => 'Ease In Sine',
			'easeOutSine' => 'Ease Out Sine',
			'easeInOutSine' => 'Ease In Out Sine',
			'easeInCirc' => 'Ease In Circ',
			'easeOutCirc' => 'Ease Out Circ',
			'easeInOutCirc' => 'Ease In Out Circ',
			'easeInElastic' => 'Ease In Elastic',
			'easeOutElastic' => 'Ease Out Elastic',
			'easeInOutElastic' => 'Ease In Out Elastic',
			'easeInBack' => 'Ease In Back',
			'easeOutBack' => 'Ease Out Back',
			'easeInOutBack' => 'Ease In Out Back',
			'easeInBounce' => 'Ease In Bounce',
			'easeOutBounce' => 'Ease Out Bounce',
			'easeInOutBounce' => 'Ease In Out Bounce'
		);

		$selectedPost = self::getSelectedPost();

		$devices = array(
			'desktop' => 'Desktop',
			'tablet' => 'Tablet',
			'mobile' => 'Mobile'
		);

		$dimensionsMode = array(
			'template' => array(
				'fieldWrapperAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper'
				),
				'labelAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper yrm-sub-option-label'
				),
				'groupWrapperAttr' => array(
					'class' => 'row form-group yrm-choice-wrapper yrm-choice-inputs-wrapper'
				)
			),
			'buttonPosition' => 'right',
			'nextNewLine' => true,
			'fields' => array(
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-dimension-mode',
						'class' => 'dimension-mode',
						'data-attr-href' => 'dimension-mode-classic',
						'value' => 'classicMode'
					),
					'label' => array(
						'name' => __('Custom Dimensions', YRM_LANG).':'
					)
				),
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-dimension-mode',
						'class' => 'dimension-mode',
						'data-attr-href' => 'dimension-mode-auto',
						'value' => 'autoMode'
					),
					'label' => array(
						'name' => __('Auto', YRM_LANG).':'
					)
				)
			)
		);
		
		$iconsMode = array(
			'template' => array(
				'fieldWrapperAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper'
				),
				'labelAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper yrm-sub-option-label'
				),
				'groupWrapperAttr' => array(
					'class' => 'row form-group yrm-choice-wrapper yrm-choice-inputs-wrapper'
				)
			),
			'buttonPosition' => 'right',
			'nextNewLine' => true,
			'fields' => array(
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-button-icon-type',
						'class' => 'dimension-mode',
						'data-attr-href' => 'yrm-button-icon-image',
						'value' => 'image'
					),
					'label' => array(
						'name' => __('Image', YRM_LANG).':'
					)
				),
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-button-icon-type',
						'class' => 'dimension-mode',
						'data-attr-href' => 'yrm-button-icon-wrapper',
						'value' => 'icon'
					),
					'label' => array(
						'name' => __('Icon', YRM_LANG).':'
					)
				)
			)
		);

		$accordionModes = array(
			'template' => array(
				'fieldWrapperAttr' => array(
					'class' => 'yrm-choice-option-wrapper'
				),
				'labelAttr' => array(
					'class' => 'yrm-choice-option-wrapper yrm-sub-option-label'
				),
				'groupWrapperAttr' => array(
					'class' => ' form-group yrm-inline-button-label yrm-choice-wrapper yrm-choice-inputs-wrapper'
				)
			),
			'buttonPosition' => 'left',
			'nextNewLine' => true,
			'fields' => array(
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-accordion-mode',
						'class' => 'accordion-mode',
						'data-attr-href' => '',
						'value' => 'allFolded'
					),
					'label' => array(
						'name' => __('All Folded', YRM_LANG)
					)
				),
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-accordion-mode',
						'class' => 'accordion-mode',
						'data-attr-href' => '',
						'value' => 'firstOpen'
					),
					'label' => array(
						'name' => __('First Open', YRM_LANG)
					)
				),
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-accordion-mode',
						'class' => 'accordion-mode',
						'data-attr-href' => '',
						'value' => 'allOpen'
					),
					'label' => array(
						'name' => __('All Open', YRM_LANG)
					)
				)
			)
		);

		$hiddenDataLoadMode = array(
			'template' => array(
				'fieldWrapperAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper'
				),
				'labelAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper yrm-sub-option-label'
				),
				'groupWrapperAttr' => array(
					'class' => 'row form-group yrm-choice-wrapper yrm-choice-inputs-wrapper'
				)
			),
			'buttonPosition' => 'right',
			'nextNewLine' => true,
			'fields' => array(
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-hidden-data-load-mode',
						'class' => 'hidden-load-mode',
						'data-attr-href' => 'after-page-load-section',
						'value' => 'onload'
					),
					'label' => array(
						'name' => __('After page load', YRM_LANG).':'
					)
				),
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-hidden-data-load-mode',
						'class' => 'hidden-load-mode',
						'data-attr-href' => 'after-button-click-section',
						'value' => 'click'
					),
					'label' => array(
						'name' => __('After click', YRM_LANG).':'
					)
				)
			)
		);

		$buttonForPost = array(
			'template' => array(
				'fieldWrapperAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper'
				),
				'labelAttr' => array(
					'class' => 'col-xs-5 yrm-choice-option-wrapper yrm-sub-option-label'
				),
				'groupWrapperAttr' => array(
					'class' => 'row form-group yrm-choice-wrapper yrm-choice-inputs-wrapper'
				)
			),
			'buttonPosition' => 'right',
			'nextNewLine' => true,
			'fields' => array(
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-button-for-post',
						'class' => 'button-for-post',
						'value' => 'forALlPosts'
					),
					'label' => array(
						'name' => __('Show on all posts', YRM_LANG).':'
					)
				),
				array(
					'attr' => array(
						'type' => 'radio',
						'name' => 'yrm-button-for-post',
						'class' => 'button-for-post',
						'data-attr-href' => 'botton-for-selected-posts',
						'value' => 'slectedPosts'
					),
					'label' => array(
						'name' => __('Show on selected post', YRM_LANG).':'
					)
				)
			)
		);

		$bgImageSize = array(
			'auto' => __('Auto', YRM_LANG),
			'cover' => __('Cover', YRM_LANG),
			'contain' => __('Contain', YRM_LANG)
		);

		$bgImageRepeat = array(
			'repeat' => __('Repeat', YRM_LANG),
			'repeat-x' => __('Repeat x', YRM_LANG),
			'repeat-y' => __('Repeat y', YRM_LANG),
			'no-repeat' => __('Not Repeat', YRM_LANG)
		);

        $themesPopup = array(
            'colorbox1',
            'colorbox2',
            'colorbox3',
            'colorbox4',
            'colorbox5'
        );
        
        $hiddenContentAlign = array(
        	'' => __('Inherit', YRM_LANG),
        	'center' => __('Center', YRM_LANG),
        	'left' => __('Left', YRM_LANG),
        	'right' => __('Right', YRM_LANG),
        	'justify' => __('Justify', YRM_LANG),
        );

        $hiddenContentLineHeight = array(
        	'' => __('Default', YRM_LANG),
        	'customLineHeight' => __('Custom', YRM_LANG)
        );

        $textDecorationType = array(
			'overline' => 'Overline',
	        'line-through' => 'Line-through',
	        'underline' => 'Underline',
	        'underline overline' => 'Underline overline'
        );

        $textDecorationStyle = array(
        	'solid' => 'Solid',
        	'double' => 'Double',
        	'dotted' => 'Dotted',
        	'dashed' => 'Dashed',
        	'wavy' => 'Wavy'
        );

        $borderStyle = $textDecorationStyle;

        $accordionOpenCloseIcons = array(
        	'fa-chevron-right_fa-chevron-down' => 'Type 1',
        	'fa-plus_fa-minus' => 'Type 2',
        	'fa-check_fa-xmark' => 'Type 3',
        	'fa-hand-point-right_fa-hand-point-down' => 'Type 4',
        	'fa-caret-up_fa-caret-down' => 'Type 5',
        );
        
		$buttonOpenCloseIcons = array(
        	'fa-chevron-right_fa-chevron-down' => 'Type 1',
        	'fa-plus_fa-minus' => 'Type 2',
        	'fa-check_fa-xmark' => 'Type 3',
        	'fa-hand-point-right_fa-hand-point-up' => 'Type 4',
        	'fa-caret-up_fa-caret-down' => 'Type 5',
        );

		$arrays = array(
			'hiddenContentLineHeight' => $hiddenContentLineHeight,
			'hiddenContentAlign' => $hiddenContentAlign,
			'horizontalAlign' => $horizontalAlign,
			'arrowIconAlignment' => $arrowIconAlignment,
			'vertical' => $vertical,
			'googleFonts' => $googleFonts,
			'hoverEffect' => $hoverEffect,
			'cursor' => $cursor,
			'selectedPost' => $selectedPost,
			'btnFontWeight' => $btnFontWeight,
			'easings' => apply_filters('yrm-easings', $easings),
			'userRoles' => self::getAllUserRoles(),
			'devices' => $devices,
			'dimensionsMode' => $dimensionsMode,
			'iconsMode' => $iconsMode,
			'bgImageSize' => $bgImageSize,
			'bgImageRepeat' => $bgImageRepeat,
			'themesPopup' => $themesPopup,
			'buttonForPost' => $buttonForPost,
			'hiddenDataLoadMode' => $hiddenDataLoadMode,
			'accordionModes' => $accordionModes,
			'textDecorationType' => $textDecorationType,
			'textDecorationStyle' => $textDecorationStyle,
			'accordionOpenCloseIcons' => $accordionOpenCloseIcons,
			'buttonOpenCloseIcons' => $buttonOpenCloseIcons,
			'borderStyle' => $borderStyle
		);

		$arrays['border-style'] = array(
			'dotted' => __('Dotted', YRM_LANG),
			'dashed' => __('Dashed', YRM_LANG),
			'solid' => __('Solid', YRM_LANG),
			'double' => __('Double', YRM_LANG),
			'groove' => __('Groove', YRM_LANG),
			'ridge' => __('Ridge', YRM_LANG),
			'inset' => __('Inset', YRM_LANG),
			'outset' => __('Outset', YRM_LANG),
			'none' => __('None', YRM_LANG),
			'hidden' => __('Hidden', YRM_LANG)
		);

		$arrays['activateEvent'] = array(
			'click' => __('Click', YRM_LANG),
			'mouseover' => __('Mouseover', YRM_LANG)
		);

		return apply_filters('yrmDefaultParamsArray', $arrays);
	}

	public static function getAllUserRoles() {
		$rulesArray = array();
		global $wp_roles;
		if(empty($wp_roles)){
			return $rulesArray;
		}

		$roles = $wp_roles->roles;

		if (empty($roles)) {
			return $rulesArray;
		}
		foreach($roles as $roleName => $roleInfo) {

			if($roleName == 'administrator') {
				continue;
			}
			$rulesArray[$roleName] = $roleName;
		}

		return $rulesArray;
	}

	public static function getCurrentUserRole() {
		$role = 'administrator';

		if (is_multisite()) {

			$getUsersObj = get_users(
				array(
					'blog_id' => get_current_blog_id()
				)
			);
			if (is_array($getUsersObj)) {
				foreach ($getUsersObj as $key => $userData) {
					if ($userData->ID == get_current_user_id()) {
						$roles = $userData->roles;
						if (is_array($roles) && !empty($roles)) {
							$role = $roles[0];
						}
					}
				}
			}

			return $role;
		}

		global $current_user, $wpdb;
		$userRoleKey = $wpdb->prefix . 'capabilities';
		if(!empty($current_user->$userRoleKey)) {
			$usersRoles = array_keys(@$current_user->$userRoleKey);
		}

		if (!empty($usersRoles) && is_array($usersRoles)) {
			$role = $usersRoles[0];
		}

		return $role;
	}

	public function defaultData() {

		$dataDefault = array(
			'button-width' => '100px',
			'button-height' => '32px',
			'animation-duration' => '1000',
			'font-size' => '14px',
			'btn-background-color' => '#81d742',
			'btn-text-color' => '',
			'btn-border-radius' => '0px',
			'horizontal' => 'center',
			'vertical' => 'bottom',
			'hidden-content-bg-color' => '',
			'hidden-inner-width' => '100%',
			'hidden-content-text-color' => '',
			'show-only-devices' => '',
			'yrm-show-date-range' => '',
			'hide-content' => 'on',
			'type' => 'button',
			'expander-font-family' => 'Open Sans',
			'hidden-content-font-size' => '12',
			'hover-effect' => '',
			'btn-hover-text-color' => 'btn-hover-text-color',
			'btn-hover-bg-color' => 'btn-hover-bg-color',
			'yrm-selected-post' => '',
			'hidden-content-padding' => '0',
			'hidden-content-padding' => '0',
			'button-border-width' => '1px',
			'button-box-shadow-horizontal-length' => '10',
			'button-box-shadow-vertical-length' => '10',
			'button-bottom-border-width' => '1px',
			'button-box-spread-radius' => '0',
			'button-box-blur-radius' => '5',
			'more-button-title' => __('Read more', YRM_LANG),
			'less-button-title' => __('Less more', YRM_LANG),
			'yrm-dimension-mode' => 'classicMode',
			'yrm-button-icon-type' => 'icon',
			'yrm-button-padding-top' => '0',
			'yrm-button-click-sound' => YRM_SOUNDS_URL."/click.mp3",
			'yrm-button-padding-right' => '0',
			'yrm-button-padding-bottom' => '0',
			'yrm-button-padding-left' => '0',
			'yrm-hidden-content-line-height-size' => '1',
			'show-content-gradient-height' => 80,
			'show-content-gradient-position' => -160,
			'show-content-gradient-color' => '#ffffff',
			'arrow-icon-width' => '20',
			'arrow-icon-height' => '20',
			'arrow-icon-alignment' => 'left',
			'yrm-button-for-post' => 'forALlPosts',
			'hidden-content-bg-img-size' => 'cover',
			'auto-open-delay' => 0,
			'auto-close-delay' => 0,
			'yrm-button-opacity' => 1,
			'hidden-content-bg-repeat' => 'no-repeat',
			'yrm-hidden-data-load-mode' => 'onload',
			'load-data-delay' => 0,
			'link-button-confirm-text' => __('Are you sure?', YRM_LANG),
            'enable-tooltip-text' => __('Info text', YRM_LANG),
            'tooltip-text-size' => '16px',
            'tooltip-text-color' => '#ffffff',
            'tooltip-text-bg-color' => '#555',
			'yrm-enable-decoration' => '',
			'yrm-decoration-type' => 'underline',
			'yrm-decoration-color' => '',
			'yrm-decoration-style' => 'solid',
			'yrm-accordion-mode' => 'allFolded'
		);

		/*popup default values*/
		$dataDefault['yrm-popup-theme'] = 'colorbox1';
		$dataDefault['yrm-popup-width'] = '';
		$dataDefault['yrm-popup-height'] = '';
		$dataDefault['yrm-popup-max-width'] = '';
		$dataDefault['yrm-popup-max-height'] = '';
		$dataDefault['yrm-popup-initial-width'] = '300';
		$dataDefault['yrm-popup-initial-height'] = '100';
		$dataDefault['yrm-popup-esc-key'] = true;
		$dataDefault['yrm-popup-close-button'] = true;
		$dataDefault['yrm-popup-overlay-click'] = true;
		$dataDefault['yrm-popup-overlay-color'] = '';
		$dataDefault['yrm-popup-content-color'] = '';
		$dataDefault['yrm-popup-content-padding'] = 0;
		$dataDefault['yrm-btn-font-weight'] = 'normal';
		$dataDefault['yrm-animate-easings'] = 'swing';

		$dataDefault['yrm-button-icon'] = YRM_BUTTON_ICON_URL;
        $dataDefault = apply_filters('yrmDefaultOptions', $dataDefault);
		return $dataDefault;
	}

	public static function getAllData() {

		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM ".sanitize_text_field($wpdb->prefix)."expm_maker ORDER BY ID DESC", ARRAY_A);

		return $results;
	}

	public static function getAllDataByLimit($offset, $limit = 1, $orderBySql = 'id', $orderSql = 'desc') {

		global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}expm_maker ORDER BY ".esc_sql($orderBySql)." ".esc_sql($orderSql)." LIMIT %d, %d",
            $offset,
            $limit
        );

		$results = $wpdb->get_results($query, ARRAY_A);

		return $results;
	}

    public static function getAccordionAllDataByLimit($offset, $limit = 1, $orderBySql = 'id', $orderSql = 'desc') {

		global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}expm_maker WHERE type=\"accordion\" ORDER BY ".esc_sql($orderBySql)." ".esc_sql($orderSql)." LIMIT %d, %d",
            $offset,
            $limit
        );

		$results = $wpdb->get_results($query, ARRAY_A);

		return $results;
	}

	public static function getAllSearchSaved($offset, $limit = 1, $orderBySql = 'id', $orderSql = 'desc') {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}" . YRM_FIND_TABLE . " ORDER BY %s %s LIMIT %d, %d",
			$orderBySql,
			$orderSql,
			$offset,
			$limit
		);

		$results = $wpdb->get_results($query, ARRAY_A);

		return $results;
	}

	public static function getDataArrayFormDb() {

		$dbData = self::getAllData();
		$data['id'] = $dbData['id'];
		$data['type'] = $dbData['type'];
		$data['title'] = $dbData['title'];
		$data['width'] = $dbData['width'];
		$data['height'] = $dbData['height'];
		$data['duration'] = $dbData['duration'];

		return array_merge($data, $dbData);
	}

	public function getOptionsData() {

		$id = $this->getId();

		if(isset($id)) {
			return $this->getSavedOptions();
		}
		else {
			return $this->defaultData();
		}
	}

	public function getOptionValue($optionKey, $isBool = false) {

		$savedOptions = $this->getSavedOptions();

		$defaultOptions = $this->defaultData();

		if (isset($savedOptions[$optionKey])) {
			$elementValue = $savedOptions[$optionKey];
		}
		else if(!empty($savedOptions) && $isBool) {
			/*for checkbox elements when they does not exist in the saved data*/
			$elementValue = @$defaultOptions[$optionKey];
		}
		else if(isset($defaultOptions[$optionKey])){
			$elementValue =  $defaultOptions[$optionKey];
		}
		else {
			$elementValue = '';
		}

		if($isBool) {
			$elementValue = $this->boolToChecked($elementValue);
		}

		return $elementValue;
	}

	public function boolToChecked($var) {
		return ($var?'checked':'');
	}

	public function delete() {

		global $wpdb;
		$id = $this->getId();
		$wpdb->delete($wpdb->prefix.'expm_maker', array('id'=>$id), array('%d'));
	}

	public static function getSelectedPost() {

		$args = array(
			'offset'           => 0,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post_status'      => 'publish',
			'suppress_filters' => true
		);

		$args["post_type"] = 'post';
		$args["posts_per_page"] = 1000;
		$pages = get_posts($args);
		$postData = array();

		if(!empty($pages)) {
			foreach ($pages as $page) {
				$postData[$page->ID] = $page->post_name;
			}
		}
	
		return $postData;
	}

	public static function getAllReadMores() {
		global $wpdb;

		$getSavedSql = "SELECT * FROM {$wpdb->prefix}expm_maker";
		$results = $wpdb->get_results($getSavedSql, ARRAY_A);

		return $results;
	}

	public static function getReadMoresIdAndTitle()
	{
		$allData = self::getAllReadMores();
		$idAndTitle = array();

		foreach ($allData as $data) {
			$title = '(Not Provided)';
			if (!empty($data['expm-title'])) {
				$title = $data['expm-title'];
			}
			$idAndTitle[$data['id']] = $title;
		}

		return $idAndTitle;
	}

	public static function getTimeZones() {

		return array(
			"" => "Per User",
			"Pacific/Midway"=>"(GMT-11:00) Midway",
			"Pacific/Niue"=>"(GMT-11:00) Niue",
			"Pacific/Pago_Pago"=>"(GMT-11:00) Pago Pago",
			"Pacific/Honolulu"=>"(GMT-10:00) Hawaii Time",
			"Pacific/Rarotonga"=>"(GMT-10:00) Rarotonga",
			"Pacific/Tahiti"=>"(GMT-10:00) Tahiti",
			"Pacific/Marquesas"=>"(GMT-09:30) Marquesas",
			"America/Anchorage"=>"(GMT-09:00) Alaska Time",
			"Pacific/Gambier"=>"(GMT-09:00) Gambier",
			"America/Los_Angeles"=>"(GMT-08:00) Pacific Time",
			"America/Tijuana"=>"(GMT-08:00) Pacific Time - Tijuana",
			"America/Vancouver"=>"(GMT-08:00) Pacific Time - Vancouver",
			"America/Whitehorse"=>"(GMT-08:00) Pacific Time - Whitehorse",
			"Pacific/Pitcairn"=>"(GMT-08:00) Pitcairn",
			"America/Dawson_Creek"=>"(GMT-07:00) Mountain Time - Dawson Creek",
			"America/Denver"=>"(GMT-07:00) Mountain Time",
			"America/Edmonton"=>"(GMT-07:00) Mountain Time - Edmonton",
			"America/Hermosillo"=>"(GMT-07:00) Mountain Time - Hermosillo",
			"America/Mazatlan"=>"(GMT-07:00) Mountain Time - Chihuahua, Mazatlan",
			"America/Phoenix"=>"(GMT-07:00) Mountain Time - Arizona",
			"America/Yellowknife"=>"(GMT-07:00) Mountain Time - Yellowknife",
			"America/Belize"=>"(GMT-06:00) Belize",
			"America/Chicago"=>"(GMT-06:00) Central Time",
			"America/Costa_Rica"=>"(GMT-06:00) Costa Rica",
			"America/El_Salvador"=>"(GMT-06:00) El Salvador",
			"America/Guatemala"=>"(GMT-06:00) Guatemala",
			"America/Managua"=>"(GMT-06:00) Managua",
			"America/Mexico_City"=>"(GMT-06:00) Central Time - Mexico City",
			"America/Regina"=>"(GMT-06:00) Central Time - Regina",
			"America/Tegucigalpa"=>"(GMT-06:00) Central Time - Tegucigalpa",
			"America/Winnipeg"=>"(GMT-06:00) Central Time - Winnipeg",
			"Pacific/Easter"=>"(GMT-06:00) Easter Island",
			"Pacific/Galapagos"=>"(GMT-06:00) Galapagos",
			"America/Bogota"=>"(GMT-05:00) Bogota",
			"America/Cayman"=>"(GMT-05:00) Cayman",
			"America/Guayaquil"=>"(GMT-05:00) Guayaquil",
			"America/Havana"=>"(GMT-05:00) Havana",
			"America/Iqaluit"=>"(GMT-05:00) Eastern Time - Iqaluit",
			"America/Jamaica"=>"(GMT-05:00) Jamaica",
			"America/Lima"=>"(GMT-05:00) Lima",
			"America/Montreal"=>"(GMT-05:00) Eastern Time - Montreal",
			"America/Nassau"=>"(GMT-05:00) Nassau",
			"America/New_York"=>"(GMT-05:00) Eastern Time",
			"America/Panama"=>"(GMT-05:00) Panama",
			"America/Port-au-Prince"=>"(GMT-05:00) Port-au-Prince",
			"America/Rio_Branco"=>"(GMT-05:00) Rio Branco",
			"America/Toronto"=>"(GMT-05:00) Eastern Time - Toronto",
			"America/Caracas"=>"(GMT-04:30) Caracas",
			"America/Antigua"=>"(GMT-04:00) Antigua",
			"America/Asuncion"=>"(GMT-04:00) Asuncion",
			"America/Barbados"=>"(GMT-04:00) Barbados",
			"America/Boa_Vista"=>"(GMT-04:00) Boa Vista",
			"America/Campo_Grande"=>"(GMT-04:00) Campo Grande",
			"America/Cuiaba"=>"(GMT-04:00) Cuiaba",
			"America/Curacao"=>"(GMT-04:00) Curacao",
			"America/Grand_Turk"=>"(GMT-04:00) Grand Turk",
			"America/Guyana"=>"(GMT-04:00) Guyana",
			"America/Halifax"=>"(GMT-04:00) Atlantic Time - Halifax",
			"America/La_Paz"=>"(GMT-04:00) La Paz",
			"America/Manaus"=>"(GMT-04:00) Manaus",
			"America/Martinique"=>"(GMT-04:00) Martinique",
			"America/Port_of_Spain"=>"(GMT-04:00) Port of Spain",
			"America/Porto_Velho"=>"(GMT-04:00) Porto Velho",
			"America/Puerto_Rico"=>"(GMT-04:00) Puerto Rico",
			"America/Santiago"=>"(GMT-04:00) Santiago",
			"America/Santo_Domingo"=>"(GMT-04:00) Santo Domingo",
			"America/Thule"=>"(GMT-04:00) Thule",
			"Antarctica/Palmer"=>"(GMT-04:00) Palmer",
			"Atlantic/Bermuda"=>"(GMT-04:00) Bermuda",
			"America/St_Johns"=>"(GMT-03:30) Newfoundland Time - St. Johns",
			"America/Araguaina"=>"(GMT-03:00) Araguaina",
			"America/Argentina/Buenos_Aires"=>"(GMT-03:00) Buenos Aires",
			"America/Bahia"=>"(GMT-03:00) Salvador",
			"America/Belem"=>"(GMT-03:00) Belem",
			"America/Cayenne"=>"(GMT-03:00) Cayenne",
			"America/Fortaleza"=>"(GMT-03:00) Fortaleza",
			"America/Godthab"=>"(GMT-03:00) Godthab",
			"America/Maceio"=>"(GMT-03:00) Maceio",
			"America/Miquelon"=>"(GMT-03:00) Miquelon",
			"America/Montevideo"=>"(GMT-03:00) Montevideo",
			"America/Paramaribo"=>"(GMT-03:00) Paramaribo",
			"America/Recife"=>"(GMT-03:00) Recife",
			"America/Sao_Paulo"=>"(GMT-03:00) Sao Paulo",
			"Antarctica/Rothera"=>"(GMT-03:00) Rothera",
			"Atlantic/Stanley"=>"(GMT-03:00) Stanley",
			"America/Noronha"=>"(GMT-02:00) Noronha",
			"Atlantic/South_Georgia"=>"(GMT-02:00) South Georgia",
			"America/Scoresbysund"=>"(GMT-01:00) Scoresbysund",
			"Atlantic/Azores"=>"(GMT-01:00) Azores",
			"Atlantic/Cape_Verde"=>"(GMT-01:00) Cape Verde",
			"Africa/Abidjan"=>"(GMT+00:00) Abidjan",
			"Africa/Accra"=>"(GMT+00:00) Accra",
			"Africa/Bissau"=>"(GMT+00:00) Bissau",
			"Africa/Casablanca"=>"(GMT+00:00) Casablanca",
			"Africa/El_Aaiun"=>"(GMT+00:00) El Aaiun",
			"Africa/Monrovia"=>"(GMT+00:00) Monrovia",
			"America/Danmarkshavn"=>"(GMT+00:00) Danmarkshavn",
			"Atlantic/Canary"=>"(GMT+00:00) Canary Islands",
			"Atlantic/Faroe"=>"(GMT+00:00) Faeroe",
			"Atlantic/Reykjavik"=>"(GMT+00:00) Reykjavik",
			"Etc/GMT"=>"(GMT+00:00) GMT (no daylight saving)",
			"Europe/Dublin"=>"(GMT+00:00) Dublin",
			"Europe/Lisbon"=>"(GMT+00:00) Lisbon",
			"Europe/London"=>"(GMT+00:00) London",
			"Africa/Algiers"=>"(GMT+01:00) Algiers",
			"Africa/Ceuta"=>"(GMT+01:00) Ceuta",
			"Africa/Lagos"=>"(GMT+01:00) Lagos",
			"Africa/Ndjamena"=>"(GMT+01:00) Ndjamena",
			"Africa/Tunis"=>"(GMT+01:00) Tunis",
			"Africa/Windhoek"=>"(GMT+01:00) Windhoek",
			"Europe/Amsterdam"=>"(GMT+01:00) Amsterdam",
			"Europe/Andorra"=>"(GMT+01:00) Andorra",
			"Europe/Belgrade"=>"(GMT+01:00) Central European Time - Belgrade",
			"Europe/Berlin"=>"(GMT+01:00) Berlin",
			"Europe/Brussels"=>"(GMT+01:00) Brussels",
			"Europe/Budapest"=>"(GMT+01:00) Budapest",
			"Europe/Copenhagen"=>"(GMT+01:00) Copenhagen",
			"Europe/Gibraltar"=>"(GMT+01:00) Gibraltar",
			"Europe/Luxembourg"=>"(GMT+01:00) Luxembourg",
			"Europe/Madrid"=>"(GMT+01:00) Madrid",
			"Europe/Malta"=>"(GMT+01:00) Malta",
			"Europe/Monaco"=>"(GMT+01:00) Monaco",
			"Europe/Oslo"=>"(GMT+01:00) Oslo",
			"Europe/Paris"=>"(GMT+01:00) Paris",
			"Europe/Prague"=>"(GMT+01:00) Central European Time - Prague",
			"Europe/Rome"=>"(GMT+01:00) Rome",
			"Europe/Stockholm"=>"(GMT+01:00) Stockholm",
			"Europe/Tirane"=>"(GMT+01:00) Tirane",
			"Europe/Vienna"=>"(GMT+01:00) Vienna",
			"Europe/Warsaw"=>"(GMT+01:00) Warsaw",
			"Europe/Zurich"=>"(GMT+01:00) Zurich",
			"Africa/Cairo"=>"(GMT+02:00) Cairo",
			"Africa/Johannesburg"=>"(GMT+02:00) Johannesburg",
			"Africa/Maputo"=>"(GMT+02:00) Maputo",
			"Africa/Tripoli"=>"(GMT+02:00) Tripoli",
			"Asia/Amman"=>"(GMT+02:00) Amman",
			"Asia/Beirut"=>"(GMT+02:00) Beirut",
			"Asia/Damascus"=>"(GMT+02:00) Damascus",
			"Asia/Gaza"=>"(GMT+02:00) Gaza",
			"Asia/Jerusalem"=>"(GMT+02:00) Jerusalem",
			"Asia/Nicosia"=>"(GMT+02:00) Nicosia",
			"Europe/Athens"=>"(GMT+02:00) Athens",
			"Europe/Bucharest"=>"(GMT+02:00) Bucharest",
			"Europe/Chisinau"=>"(GMT+02:00) Chisinau",
			"Europe/Helsinki"=>"(GMT+02:00) Helsinki",
			"Europe/Istanbul"=>"(GMT+02:00) Istanbul",
			"Europe/Kaliningrad"=>"(GMT+02:00) Moscow-01 - Kaliningrad",
			"Europe/Kiev"=>"(GMT+02:00) Kiev",
			"Europe/Riga"=>"(GMT+02:00) Riga",
			"Europe/Sofia"=>"(GMT+02:00) Sofia",
			"Europe/Tallinn"=>"(GMT+02:00) Tallinn",
			"Europe/Vilnius"=>"(GMT+02:00) Vilnius",
			"Africa/Addis_Ababa"=>"(GMT+03:00) Addis Ababa",
			"Africa/Asmara"=>"(GMT+03:00) Asmera",
			"Africa/Dar_es_Salaam"=>"(GMT+03:00) Dar es Salaam",
			"Africa/Djibouti"=>"(GMT+03:00) Djibouti",
			"Africa/Kampala"=>"(GMT+03:00) Kampala",
			"Africa/Khartoum"=>"(GMT+03:00) Khartoum",
			"Africa/Mogadishu"=>"(GMT+03:00) Mogadishu",
			"Africa/Nairobi"=>"(GMT+03:00) Nairobi",
			"Antarctica/Syowa"=>"(GMT+03:00) Syowa",
			"Asia/Aden"=>"(GMT+03:00) Aden",
			"Asia/Baghdad"=>"(GMT+03:00) Baghdad",
			"Asia/Bahrain"=>"(GMT+03:00) Bahrain",
			"Asia/Kuwait"=>"(GMT+03:00) Kuwait",
			"Asia/Qatar"=>"(GMT+03:00) Qatar",
			"Asia/Riyadh"=>"(GMT+03:00) Riyadh",
			"Europe/Minsk"=>"(GMT+03:00) Minsk",
			"Europe/Moscow"=>"(GMT+03:00) Moscow+00",
			"Indian/Antananarivo"=>"(GMT+03:00) Antananarivo",
			"Indian/Comoro"=>"(GMT+03:00) Comoro",
			"Indian/Mayotte"=>"(GMT+03:00) Mayotte",
			"Asia/Tehran"=>"(GMT+03:30) Tehran",
			"Asia/Baku"=>"(GMT+04:00) Baku",
			"Asia/Dubai"=>"(GMT+04:00) Dubai",
			"Asia/Muscat"=>"(GMT+04:00) Muscat",
			"Asia/Tbilisi"=>"(GMT+04:00) Tbilisi",
			"Asia/Yerevan"=>"(GMT+04:00) Yerevan",
			"Europe/Samara"=>"(GMT+04:00) Moscow+00 - Samara",
			"Indian/Mahe"=>"(GMT+04:00) Mahe",
			"Indian/Mauritius"=>"(GMT+04:00) Mauritius",
			"Indian/Reunion"=>"(GMT+04:00) Reunion",
			"Asia/Kabul"=>"(GMT+04:30) Kabul",
			"Antarctica/Mawson"=>"(GMT+05:00) Mawson",
			"Asia/Aqtau"=>"(GMT+05:00) Aqtau",
			"Asia/Aqtobe"=>"(GMT+05:00) Aqtobe",
			"Asia/Ashgabat"=>"(GMT+05:00) Ashgabat",
			"Asia/Dushanbe"=>"(GMT+05:00) Dushanbe",
			"Asia/Karachi"=>"(GMT+05:00) Karachi",
			"Asia/Tashkent"=>"(GMT+05:00) Tashkent",
			"Asia/Yekaterinburg"=>"(GMT+05:00) Moscow+02 - Yekaterinburg",
			"Indian/Kerguelen"=>"(GMT+05:00) Kerguelen",
			"Indian/Maldives"=>"(GMT+05:00) Maldives",
			"Asia/Calcutta"=>"(GMT+05:30) India Standard Time",
			"Asia/Colombo"=>"(GMT+05:30) Colombo",
			"Asia/Katmandu"=>"(GMT+05:45) Katmandu",
			"Antarctica/Vostok"=>"(GMT+06:00) Vostok",
			"Asia/Almaty"=>"(GMT+06:00) Almaty",
			"Asia/Bishkek"=>"(GMT+06:00) Bishkek",
			"Asia/Dhaka"=>"(GMT+06:00) Dhaka",
			"Asia/Omsk"=>"(GMT+06:00) Moscow+03 - Omsk, Novosibirsk",
			"Asia/Thimphu"=>"(GMT+06:00) Thimphu",
			"Indian/Chagos"=>"(GMT+06:00) Chagos",
			"Asia/Rangoon"=>"(GMT+06:30) Rangoon",
			"Indian/Cocos"=>"(GMT+06:30) Cocos",
			"Antarctica/Davis"=>"(GMT+07:00) Davis",
			"Asia/Bangkok"=>"(GMT+07:00) Bangkok",
			"Asia/Hovd"=>"(GMT+07:00) Hovd",
			"Asia/Jakarta"=>"(GMT+07:00) Jakarta",
			"Asia/Krasnoyarsk"=>"(GMT+07:00) Moscow+04 - Krasnoyarsk",
			"Asia/Saigon"=>"(GMT+07:00) Hanoi",
			"Indian/Christmas"=>"(GMT+07:00) Christmas",
			"Antarctica/Casey"=>"(GMT+08:00) Casey",
			"Asia/Brunei"=>"(GMT+08:00) Brunei",
			"Asia/Choibalsan"=>"(GMT+08:00) Choibalsan",
			"Asia/Hong_Kong"=>"(GMT+08:00) Hong Kong",
			"Asia/Irkutsk"=>"(GMT+08:00) Moscow+05 - Irkutsk",
			"Asia/Kuala_Lumpur"=>"(GMT+08:00) Kuala Lumpur",
			"Asia/Macau"=>"(GMT+08:00) Macau",
			"Asia/Makassar"=>"(GMT+08:00) Makassar",
			"Asia/Manila"=>"(GMT+08:00) Manila",
			"Asia/Shanghai"=>"(GMT+08:00) China Time - Beijing",
			"Asia/Singapore"=>"(GMT+08:00) Singapore",
			"Asia/Taipei"=>"(GMT+08:00) Taipei",
			"Asia/Ulaanbaatar"=>"(GMT+08:00) Ulaanbaatar",
			"Australia/Perth"=>"(GMT+08:00) Western Time - Perth",
			"Asia/Dili"=>"(GMT+09:00) Dili",
			"Asia/Jayapura"=>"(GMT+09:00) Jayapura",
			"Asia/Pyongyang"=>"(GMT+09:00) Pyongyang",
			"Asia/Seoul"=>"(GMT+09:00) Seoul",
			"Asia/Tokyo"=>"(GMT+09:00) Tokyo",
			"Asia/Yakutsk"=>"(GMT+09:00) Moscow+06 - Yakutsk",
			"Pacific/Palau"=>"(GMT+09:00) Palau",
			"Australia/Adelaide"=>"(GMT+09:30) Central Time - Adelaide",
			"Australia/Darwin"=>"(GMT+09:30) Central Time - Darwin",
			"Antarctica/DumontDUrville"=>"(GMT+10:00) Dumont D'Urville",
			"Asia/Magadan"=>"(GMT+10:00) Moscow+08 - Magadan",
			"Asia/Vladivostok"=>"(GMT+10:00) Moscow+07 - Yuzhno-Sakhalinsk",
			"Australia/Brisbane"=>"(GMT+10:00) Eastern Time - Brisbane",
			"Australia/Hobart"=>"(GMT+10:00) Eastern Time - Hobart",
			"Australia/Sydney"=>"(GMT+10:00) Eastern Time - Melbourne, Sydney",
			"Pacific/Chuuk"=>"(GMT+10:00) Truk",
			"Pacific/Guam"=>"(GMT+10:00) Guam",
			"Pacific/Port_Moresby"=>"(GMT+10:00) Port Moresby",
			"Pacific/Saipan"=>"(GMT+10:00) Saipan",
			"Pacific/Efate"=>"(GMT+11:00) Efate",
			"Pacific/Guadalcanal"=>"(GMT+11:00) Guadalcanal",
			"Pacific/Kosrae"=>"(GMT+11:00) Kosrae",
			"Pacific/Noumea"=>"(GMT+11:00) Noumea",
			"Pacific/Pohnpei"=>"(GMT+11:00) Ponape",
			"Pacific/Norfolk"=>"(GMT+11:30) Norfolk",
			"Asia/Kamchatka"=>"(GMT+12:00) Moscow+08 - Petropavlovsk-Kamchatskiy",
			"Pacific/Auckland"=>"(GMT+12:00) Auckland",
			"Pacific/Fiji"=>"(GMT+12:00) Fiji",
			"Pacific/Funafuti"=>"(GMT+12:00) Funafuti",
			"Pacific/Kwajalein"=>"(GMT+12:00) Kwajalein",
			"Pacific/Majuro"=>"(GMT+12:00) Majuro",
			"Pacific/Nauru"=>"(GMT+12:00) Nauru",
			"Pacific/Tarawa"=>"(GMT+12:00) Tarawa",
			"Pacific/Wake"=>"(GMT+12:00) Wake",
			"Pacific/Wallis"=>"(GMT+12:00) Wallis",
			"Pacific/Apia"=>"(GMT+13:00) Apia",
			"Pacific/Enderbury"=>"(GMT+13:00) Enderbury",
			"Pacific/Fakaofo"=>"(GMT+13:00) Fakaofo",
			"Pacific/Tongatapu"=>"(GMT+13:00) Tongatapu",
			"Pacific/Kiritimati"=>"(GMT+14:00) Kiritimati"
		);
	}
}