<?php

const API_KEY = 'kq8rezafeaxv47bhn8s65m4m';
const ITEM_COUNT = 30;

const ICO_BLANK = './icons/blank/';
const ICO_GEN = './icons/generated/';

require_once('workflows.php');
$w = new Workflows();

$query = trim( @$argv[1] );
$query_encoded = urlencode( $query );

if ( strlen( $query ) < 2 ):
    return;
endif;

if ( !file_exists( ICO_GEN ) ):
    mkdir( ICO_GEN, 0777, true );
endif;

$source = "http://api.rottentomatoes.com/api/public/v1.0/movies.json";
$args = array( 'apikey' => API_KEY,
               'page_limit' => ITEM_COUNT,
               'q' => $query );

$params = http_build_query( $args );
$json = $w->request( $source."?".$params );
$data = json_decode( $json );

$total = $data->total;
$movies = $data->movies;

if ( $total > 0 ):

    foreach ( $movies as $movie ):
        $id = $movie->id;
        $title = $movie->title;
        $year = $movie->year;
        $cast = (array) $movie->abridged_cast;
        $link = $movie->links->alternate;
        $ratings = $movie->ratings;
        $score = $ratings->critics_score;
        $cert = $ratings->critics_rating;
        $icon_path = ICO_GEN.$score.'.png';
        $label = "$year";

        if ( $score < 0 ):
            $icon_path = ICO_BLANK.'logo.png';
        elseif ( !file_exists( $icon_path ) ):
            $image = generate_icon( $score, $cert );
            imagepng ( $image, $icon_path );
            imagedestroy( $image );
        endif;


        if ( count($cast) > 0 ):
            $a = array_map( function($obj) { return "$obj->name"; }, $cast);
            $cast_label = implode( ', ', $a );
            $label.= " - $cast_label";
        endif;

        $w->result( "tomato-".$id,
                    "$link",
                    "$title",
                    "$label",
                    "$icon_path" );
    endforeach;
else:
    $w->result( "tomato-noentry",
                "http://www.rottentomatoes.com/search/?search=".$query_encoded,
                "No movie ratings found",
                "Search Rotten Tomatoes for ".$query,
                "blank/rotten.png" );
endif;

echo $w->toxml();

function generate_icon( $score, $cert ) {
    // todo: $cert needs implementation
    $template = NULL;
    $x_offset = 0;
    $y_offset = 0;
    $label = $score;

    if ( $score >= 60 ):
        $template = ICO_BLANK.'fresh.png';
        $y_offset = 20;
    else:
        $template = ICO_BLANK.'rotten.png';
        $x_offset = -10;
    endif;

    $font = './fonts/AlteHaasGroteskBold.ttf';
    $size = 70;

    $image = imagecreatefrompng( $template );
    imagealphablending( $image, false );

    $box = imageftbbox( $size, 0, $font, $label );
    $box_width = floor($box[4] - $box[6]);
    $box_height = floor($box[1] - $box[7]);
    $x = floor((imagesx($image) - $box_width) / 2);
    $x += $x_offset;
    $y = floor((imagesy($image) - $box_height) / 2);
    $y += $box_height;
    $y += $y_offset;

    $grey = imagecolorallocate( $image, 255, 255, 255 );
    $black = imagecolorallocate( $image, 0, 0, 0 );

    imagealphablending($image, true);
    imagefttext($image, $size, $angle, $x, $y+3, $grey, $font, $label );
    imagefttext($image, $size, $angle, $x, $y, $colour, $font, $label );
    imagealphablending($image, false);
    imagesavealpha($image, true);

    return $image;
}

?>
