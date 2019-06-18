<?php namespace Rvwoens\Former;

use \Log, \Imagick, \URL;

class imgscale {
	// yum install php-pecl-imagick
	// yum install ImageMagick (part of pecl-imagick)


	public static function getScaledFileName($infile,$w=0,$h=0) {
		$pinfile=pathinfo($infile);
		$basepath=$pinfile['dirname'].'/'.$pinfile['filename'];
		if ($w && $h)
			return $basepath.sprintf("_C%dx%d.png",$w,$h);	// cropped WxH
		if ($w && !$h)
			return $basepath.sprintf("_W%d.png",$w);
		if (!$w && $h)
			return $basepath.sprintf("_H%d.png",$h);
		return $infile;	// no conversion
	}

	public static function path2url($file) {
		if (strpos($file,'http://')!==false)
			return $file;	// already an url
		if ($file[0]!='/')
			$rfile=realpath($file);
		else
			$rfile=$file; // absolute OR only the part after public

		if (($ix=strpos($rfile,'public'))!==false)
			return URL::Asset(substr($rfile,$ix+strlen('public')));	// assume its an asset under public
		// maybe we need to put public path
		if ($file[0]=='/')
			$file=substr($file,1);
		return URL::Asset('/'.$file);
		// oops public not found.. cant make URL
		//Log::info("imgscale: cant make URL from $file ($rfile)");
		//	return URL::Base()."img/error.png";
	}
	public static function url2path($url) {
		if (strpos($url,URL::Asset(''))!==false) {
			// starts with our baseurl.. so we can convert
			return path('public').substr($url,strlen(URL::Asset(''))+1 );
		}
		Log::info("imgscale: cant make path from url $url");
		return false;
	}

	//-------------------------------------------------------------------
	// SEE s3fy!
	//-------------------------------------------------------------------
	// get URL of scaled/cropped (and cached) image. Infile can be URL or PATH
	// replaced by S3FY module
	public static function getScaledUrl($infile,$w=0,$h=0) {
		$cinfile=static::url2path($infile);
		if (!$cinfile)
			return $infile;	// cant make path
		$outfile=static::getScaledFileName($cinfile,$w,$h);

		// L4 no s3files cdn
		//if (\s3files::file_exists($outfile) && (!\s3files::file_exists($infile) || \s3files::file_onlycdn($infile) || filemtime($outfile)>=filemtime($cinfile))) {
		//	$ourl=static::path2url($outfile);
		//	//Log::info("imgscale: getScaledUrl from $infile ($cinfile) -> cached: $ourl");
		//	return $ourl; // cached!
		//}
		return static::path2url(static::scale($cinfile,$w,$h,$outfile));	// generate!
	}

	public static function scale($infile,$w=0,$h=0,$outfile='') {

		if (strpos($infile,'http://')===0) {
			$infile=static::url2path($infile);
			if (strpos($infile,'http://')===0) {
				return $infile;
			}
		}
		$image = new Imagick();
		if ($outfile=='') {
			$outfile=static::getScaledFileName($infile);
		}
		try {
			//Log::info("imgscale:Scaling $infile");
			@$image->readImage($infile);
			$image->setImageFormat("png");
			static::autoRotateImage($image);
			$iWidth = $image->getImageWidth(); $iHeight = $image->getImageHeight();

			if (!$w && !$h)
				// no width/height set..
				return $infile;	// do not scale
			if ($w && $h) {
				// scale AND crop
				if ($iWidth>$iHeight) {
					// landcape. Scale heigt, crop width
					$image->scaleImage(0,$h);
				}
				else {
					// Portrait. Scale width, crop height
					$image->scaleImage($w,0);
				}
				$iWidth = $image->getImageWidth(); $iHeight = $image->getImageHeight();
				// widht/height x,y
				$image->cropImage ($w , $h , round($iWidth-$w)/2 , round($iHeight-$h)/2 );
				$iWidth = $image->getImageWidth();$iHeight = $image->getImageHeight();
			}
			else {
				if ($w && !$h) {
					$h=round($w*$iHeight/$iWidth);	// scale with constraints
				}
				if (!$w && $h) {
					$w=round($h*$iWidth/$iHeight);
				}
				//Log::info("imgscale: Scaling to $w x $h");
				$image->scaleImage($w,$h);
			}
			Log::info("imgscale:writing $outfile $w x $h");
			$image->writeImage($outfile);
		}
		catch (\Exception $e) {
			Log::info("imgscale: Exception ". $e->getMessage());
			return $infile;
		}
		catch ( \ImagickEception $e ) {
			Log::info("imgscale: ImagickException ". $e->getMessage());
			return $infile;
		}
		return $outfile;
	}

	private static function autoRotateImage($image) {
		// set orientation to the default TOPLEFT value and rotate the image accordingly
		$orientation = $image->getImageOrientation();

		switch($orientation) {
		case imagick::ORIENTATION_BOTTOMRIGHT:
			$image->rotateimage("#000", 180); // rotate 180 degrees
			break;

		case imagick::ORIENTATION_RIGHTTOP:
			$image->rotateimage("#000", 90); // rotate 90 degrees CW
			break;

		case imagick::ORIENTATION_LEFTBOTTOM:
			$image->rotateimage("#000", -90); // rotate 90 degrees CCW
			break;
		}

		// Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
		$image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
	}
}