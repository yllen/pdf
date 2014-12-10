<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2014 by the pdf Development Team.

 https://forge.indepnet.net/projects/pdf
 -------------------------------------------------------------------------

 LICENSE

 This file is part of pdf.

 pdf is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 pdf is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with pdf. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define ('K_PATH_IMAGES', GLPI_ROOT.'/plugins/pdf/pics/');

require_once(GLPI_TCPDF_DIR.'/tcpdf.php');

class PluginPdfSimplePDF {

   // Page orientation
   const PORTRAIT  = 'P';
   const LANDSCAPE = 'L';

   // Cell alignment
   const LEFT   = 'L';
   const CENTER = 'C';
   const RIGHT  = 'R';

   private $df;

   // Page management
   private $width;
   private $height;
   private $start_tab;
   private $header='';

   // Columns management
   private $cols  = array();
   private $colsx = array();
   private $colsw = array();
   private $align = array();


   /**
    * @param $format    (default a4)
    * @param $orient    (default portrait)
   **/
   function __construct ($format='A4', $orient='') {

      /* Compat with 0.84 */
      if (empty($orient) || $orient=='portrait') {
         $orient = self::PORTRAIT;
      } else if ($orient=='landscape') {
         $orient = self::LANDSCAPE;
      }
      $format = strtoupper($format);

      $pdf = new TCPDF($orient, 'mm', $format, true, 'UTF-8', false);

      $pdf->SetCreator('GLPI');
      $pdf->SetAuthor('GLPI');
      $font       = 'helvetica';
      //$subsetting = true;
      $fonsize    = 8;
      if (isset($_SESSION['glpipdffont']) && $_SESSION['glpipdffont']) {
         $font       = $_SESSION['glpipdffont'];
         //$subsetting = false;
      }
      $pdf->setHeaderFont(Array($font, 'B', 8));
      $pdf->setFooterFont(Array($font, 'B', 8));

      //set margins
      $pdf->SetMargins(10, 20, 10);
      $pdf->SetHeaderMargin(10);
      $pdf->SetFooterMargin(10);

      //set auto page breaks
      $pdf->SetAutoPageBreak(true, 15);


      // For standard language
      //$pdf->setFontSubsetting($subsetting);
      // set font
      $pdf->SetFont($font, '', 8);

      $this->width  = $pdf->getPageWidth() - 20;
      $this->height = $pdf->getPageHeight() - 40;
      $this->pdf    = $pdf;
   }


   /**
    * @param $msg
   **/
   public function setHeader($msg) {
      $this->header = $msg;
      $this->pdf->SetTitle($msg);
      $this->pdf->SetHeaderData('fd_logo.jpg', 15, $msg, '');
   }


   public function render() {
      $this->pdf->Output('glpi.pdf', 'I');
   }

   public function output($name=false) {

      if (!$name) {
         return $this->pdf->Output('glpi.pdf', 'S');
      }
      $this->pdf->Output($name, 'F');
   }

   public function newPage() {

      $this->pdf->AddPage();
   }


   // Args is relative size of each column
   public function setColumnsSize() {

      $this->cols = $tmp = func_get_args();
      $this->colsx = array();
      $this->colsw = array();
      $this->align = array();

      $x=10;
      $w=floor($this->width - 2*count($tmp));

      while ($rel = array_shift($tmp)) {
         $z = $w*$rel/100;
         $this->colsx[] = $x;
         $this->colsw[] = $z;
         $x += $z+2;
      }
   }


   // Args are relative size of each column
   public function setColumnsAlign () {

      $this->align = func_get_args();
      /* compat with 0.84 */
      foreach ($this->align as $k => $v) {
         switch($v) {
            case 'left':   $this->align[$k] = self::LEFT;   break;
            case 'right':  $this->align[$k] = self::RIGHT;  break;
            case 'center': $this->align[$k] = self::CENTER; break;
         }
      }
   }


   /**
    * @param $gray
   **/
   public function displayBox($gray) {

      Toolbox::displayBox("PluginPdfSimplePDF::displayBox() is deprecated");
   }


   private function displayInternal($gray, $padd, $defalign, $miny, $msgs) {

      $this->pdf->SetFillColor($gray, $gray, $gray);
      $this->pdf->SetCellPadding($padd);

      $max = $miny;

      /* dry run - compute max cell height */
      $this->pdf->startTransaction();
      $i = 0;
      foreach ($msgs as $msg) {
         if ($i<count($this->cols)) {
            $this->pdf->writeHTMLCell(
               $this->colsw[$i], // $w (float) Cell width. If 0, the cell extends up to the right margin.
               $miny,            // $h (float) Cell minimum height. The cell extends automatically if needed.
               '',               // $x (float) upper-left corner X coordinate
               '',               // $y (float) upper-left corner Y coordinate
               $msg,             // $html (string) html text to print. Default value: empty string.
               0,                // $border (mixed) Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)))
               0,                // $ln (int) Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right (or left for RTL language)</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>
               1,                // $fill (boolean) Indicates if the cell background must be painted (true) or transparent (false).
               true,             // $reseth (boolean) if true reset the last cell height (default true).
               self::LEFT,       // $align (string) Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
               true              // $autopadding (boolean) if true, uses internal padding and automatically adjust it to account for line width.
            );
            if ($this->pdf->getLastH() > $max) {
               $max = $this->pdf->getLastH();
            }
            $i++;
         } else {
            break;
         }
      }
      $this->pdf = $this->pdf->rollbackTransaction();

      /* real run */
      $i = 0;
      foreach ($msgs as $msg) {
         if ($i<count($this->cols)) {
            if (($i+1)<count($msgs) && ($i+1)<count($this->cols)) {
               $ln = 0; // right
            } else {
               $ln = 1; // down
            }
            $this->pdf->SetX($this->colsx[$i]);
            $align = (isset($this->align[$i]) ? $this->align[$i] : $defalign);
            $this->pdf->writeHTMLCell(
               $this->colsw[$i], // $w (float) Cell width. If 0, the cell extends up to the right margin.
               $max,             // $h (float) Cell minimum height. The cell extends automatically if needed.
               '',               // $x (float) upper-left corner X coordinate
               '',               // $y (float) upper-left corner Y coordinate
               $msg,             // $html (string) html text to print. Default value: empty string.
               0,                // $border (mixed) Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)))
               $ln,              // $ln (int) Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right (or left for RTL language)</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>
               1,                // $fill (boolean) Indicates if the cell background must be painted (true) or transparent (false).
               true,             // $reseth (boolean) if true reset the last cell height (default true).
               $align,           // $align (string) Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
               true              // $autopadding (boolean) if true, uses internal padding and automatically adjust it to account for line width.
            );
            $i++;
         } else {
            break;
         }
      }
      $this->pdf->SetY($this->pdf->GetY() + 1);
   }

   public function displayTitle() {
      $this->displayInternal(200, 1.0, self::CENTER, 1, func_get_args());
   }

   public function displayLine() {
      $this->displayInternal(240, 0.5, self::LEFT, 1, func_get_args());
   }


   /**
    * @param $name
    * @param $URL
   **/
   public function displayLink($name, $URL) {

      $this->displayInternal(240, 0.5, self::LEFT, 1, array(sprintf('<a href="%s">%s</a>', $URL, $name)));
   }


   /**
    * Display a multi-line Box : 1 column only
    *
    * @param $name      string   display on the left, before text
    * @param $content   string   of text display on right (multi-line)
    * @param $minline   integer  for minimum box size (default 3)
    * @param $maxline   interger for maximum box size (1 page = 80 lines) (default 100)
   **/
   public function displayText($name, $content='', $minline=3, $maxline=100) {

      /* Save columns */
      $save = array(
         $this->cols,
         $this->colsw,
         $this->colsw,
         $this->align,
      );

      $this->setColumnsSize(100);
      $this->displayInternal(240, 0.5, self::LEFT, $minline*5, array($name.' '.$content));

      /* Restore */
      list(
         $this->cols,
         $this->colsw,
         $this->colsw,
         $this->align
      ) = $save;

   }


   /**
    * @param $nb     (default 1)
   **/
   public function displaySpace($nb=1) {

      $this->pdf->Ln(4);
   }


   /**
    * @param $image
    * @param $dst_w
    * @param $dst_h
   **/
   public function addPngFromFile($image,$dst_w,$dst_h) {

      $w = $this->pdf->pixelsToUnits($dst_w);
      $h = $this->pdf->pixelsToUnits($dst_h);

      if ($this->pdf->GetY()+$h-20 > $this->height) { /* autopagebreak seems broken */
         $this->pdf->AddPage();
      }
      $this->pdf->Image(
         $image,
         '',   // x
         '',   // y
         $w,   // $w
         $h,   // $w
         'PNG' // type
      );

      $this->pdf->SetY($this->pdf->GetY()+$h+2);
   }
}
