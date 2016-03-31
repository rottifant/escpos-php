<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

use Mike42\Escpos\EscposImage;
use Exception;

/**
 * Implementation of EscposImage using the GD PHP plugin.
 */
class GdEscposImage extends EscposImage
{

    /**
     * @param string $filename
     *  Path to load image from disk. Use 'null' to get an empty image.
     * @param string $allow_optimisations
     *  True to use library-specific speed optimisations.
     * @throws Exception
     *  Where image loading failed (eg. unsupported format, no such file, permission error).
     */
    public function __construct($filename = null, $allow_optimisations = true)
    {
        if ($filename === null) {
            return;
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($ext) {
            case "png":
                $im = @imagecreatefrompng($filename);
                break;
            case "jpg":
                $im = @imagecreatefromjpeg($filename);
                break;
            case "gif":
                $im = @imagecreatefromgif($filename);
                break;
            default:
                throw new \Exception("Image format not supported in GD");
        }
        $this -> readImageFromGdResource($im);
        // TODO implement optimised version of these methods. Rendering is too slow.
    }
    
    /**
     * Load actual image pixels from GD resource.
     *
     * @param resouce $im GD resource to use
     * @throws Exception Where the image can't be read.
     */
    public function readImageFromGdResource($im)
    {
        if (!is_resource($im)) {
            throw new Exception("Failed to load image.");
        } elseif (!$this -> isGdSupported()) {
            throw new Exception(__FUNCTION__ . " requires 'gd' extension.");
        }
        /* Make a string of 1's and 0's */
        $this -> imgHeight = imagesy($im);
        $this -> imgWidth = imagesx($im);
        $this -> imgData = str_repeat("\0", $this -> imgHeight * $this -> imgWidth);
        for ($y = 0; $y < $this -> imgHeight; $y++) {
            for ($x = 0; $x < $this -> imgWidth; $x++) {
                /* Faster to average channels, blend alpha and negate the image here than via filters (tested!) */
                $cols = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                $greyness = (int)(($cols['red'] + $cols['green'] + $cols['blue']) / 3) >> 7; // 1 for white, 0 for black
                $black = (1 - $greyness) >> ($cols['alpha'] >> 6); // 1 for black, 0 for white, taking into account transparency
                $this -> imgData[$y * $this -> imgWidth + $x] = $black;
            }
        }
    }
}