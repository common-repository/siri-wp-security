<?php


if ( !function_exists( 'hex2rgb' ) ) :
	function hex2rgb( $color ) {
		if ( $color[0] == '#' ) {
			$color = substr( $color, 1 );
		}
		if ( strlen( $color ) == 6 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return false;
		}
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return array( 'red' => $r, 'green' => $g, 'blue' => $b );
	}
endif;


if ( !function_exists( 'rgb2hex' ) ) :
function rgb2hex( $rgb ) {
   $hex  = "#";
   $hex .= str_pad( dechex( $rgb[0] ), 2, "0", STR_PAD_LEFT );
   $hex .= str_pad( dechex( $rgb[1] ), 2, "0", STR_PAD_LEFT );
   $hex .= str_pad( dechex( $rgb[2] ), 2, "0", STR_PAD_LEFT );

   return $hex; // returns the hex value including the number sign (#)
}
endif;


if ( !function_exists( 'rgba2hex' ) ) :
function rgba2hex( $rgba ) {		
//	$rgba = str_replace( array( 'rgb', 'rgba', '(', ')' ), '', $rgba );
	$rgba = preg_replace( 
		array(
			'/[^\d,]/',    // Matches anything that's not a comma or number.
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', $rgba );
	$rgba = explode( ',', $rgba );
	
	$hex  = "#";
	$hex .= str_pad( dechex( $rgba[0] ), 2, "0", STR_PAD_LEFT );
	$hex .= str_pad( dechex( $rgba[1] ), 2, "0", STR_PAD_LEFT );
	$hex .= str_pad( dechex( $rgba[2] ), 2, "0", STR_PAD_LEFT );
	
	return $hex; // returns the hex value including the number sign (#)
}
endif;

/**
 * Helper function to convert RGB(A) to array
 *
 * @return	bool
 */
if ( !function_exists( 'is_rgba' ) ) :
function is_rgba( $str ) {
	$is_rgba = strpos( $str, 'rgba' );
	
	if ( false === $is_rgba )
		return false;
	
	return true;
}
endif;


if ( !function_exists( 'prefixit' ) ) :
	function prefixit( $input, $option ) {
		$prefixs = array( '-webkit-', '-moz-', '-ms-', '-o-', '' );
		
		$output  = "\n\t";
		
		foreach ( $prefixs as $prefix ) {
			$output .= trailingsemicolonit( $prefix . $input . ': ' . esc_attr( $option ) );
		}
		
		return $output;
	}
endif;
	

if ( !function_exists( 'trailingsemicolonit' ) ) :
	function trailingsemicolonit( $input ) {
		$output  = rtrim( $input, ';' );
		$output .= ';' . "\n\t";
		
		return $output;
	}
endif;


if ( !function_exists( 'cssrule' ) ) :
	function cssrule( $rule ) {
		$output  = rtrim( $rule, '{' );
		$output .= " {\n\t";
		
		return $output;
	}
endif;