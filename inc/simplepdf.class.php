<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 pdf - Export to PDF plugin for GLPI
 Copyright (C) 2003-2011 by the pdf Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

class PluginPdfSimplePDF {

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

   function __construct ($format='a4', $orient='portrait') {

      // A4 is 595.28 x 841.89
      $this->pdf    = new Cezpdf($format,$orient);
      $this->width  = $this->pdf->ez['pageWidth'];
      $this->height = $this->pdf->ez['pageHeight'];
      $this->pdf->openHere('Fit');

      // error_log("PDF: " . $this->width . "x" . $this->height);
      $this->start_tab = $this->height;
      $this->setBackground();
   }


   private function setBackground() {

      $id_pdf = $this->pdf->openObject();
      $this->pdf->saveState();
      $this->pdf->ezStartPageNumbers($this->width-20,10,10,'left',
                                     Html::convDate(date("Y-m-d"))." - {PAGENUM}/{TOTALPAGENUM}");
      $this->pdf->setStrokeColor(0,0,0);
      $this->pdf->setLineStyle(1,'round','round');
      $this->pdf->rectangle(20,20,$this->width-40,$this->height-40);
      $this->pdf->addJpegFromFile(GLPI_ROOT."/plugins/pdf/pics/fd_logo.jpg",25,$this->height-50); // 61x25
      $this->pdf->selectFont(GLPI_ROOT."/plugins/pdf/fonts/Times-Roman.afm");
      $this->pdf->setFontFamily('Times-Roman.afm',array('b'  => 'Times-Bold.afm',
                                                        'i'  => 'Times-Italic.afm',
                                                        'bi' => 'Times-BoldItalic.afm'));
      $this->pdf->restoreState();
      $this->pdf->closeObject();
      $this->pdf->addObject($id_pdf, 'all');
   }


   public function setHeader ($msg) {
      $this->header = $msg;
   }


   public function render () {
      $this->pdf->ezStream();
   }

   public function output () {
      return $this->pdf->output();
   }

   public function newPage () {

      if ($this->start_tab<$this->height) { // This is not the first page
         $this->pdf->ezText("",1000);
         $this->pdf->ezText("",9);
      }

      $this->start_tab = $this->height-45;
      if (!empty($this->header)) {
         $this->pdf->addTextWrap(85,$this->start_tab,$this->width-110,14,
                                 Toolbox::decodeFromUtf8($this->header,"windows-1252"),'center');
         $this->start_tab -= 30;
      }
   }


   // Args is relative size of each column
   public function setColumnsSize () {

      $this->cols = $tmp = func_get_args();
      $this->colsx = array();
      $this->colsw = array();
      $this->align = array();

      $x=25;
      $w=floor($this->width-45-5*count($tmp));

      while ($rel = array_shift($tmp)) {
         $z = floor($w*$rel/100);
         $this->colsx[] = $x;
         $this->colsw[] = $z;
         $x += $z+5;
      }
   }


   // Args are relative size of each column
   public function setColumnsAlign () {
      $this->align = func_get_args();
   }


   public function displayBox ($gray) {

      $this->pdf->saveState();
      $this->pdf->setColor($gray,$gray,$gray);

      for ($i=0 ; $i<count($this->cols) ; $i++) {
         $this->pdf->filledRectangle($this->colsx[$i],$this->start_tab-5,$this->colsw[$i],15);
      }
      $this->pdf->restoreState();
   }


   public function displayTitle () {

      $msgs = func_get_args();

      // New page if less than 2 lines available
      if ($this->start_tab < 50) {
         $this->newPage();
      }

      $this->displayBox(0.80);

      $i = 0;
      foreach ($msgs as $msg) {
         if ($i<count($this->cols)) {
            $this->pdf->addTextWrap($this->colsx[$i]+2,$this->start_tab,$this->colsw[$i]-4,9,
                                    Toolbox::decodeFromUtf8($msg,"windows-1252"),
                                    (isset($this->align[$i]) ? $this->align[$i] : 'center'));
            $i++;
         } else {
            break;
         }
      }
      $this->start_tab -= 20;
   }


   public function displayLine () {

      $msgs = func_get_args();

      // New page if less than 1 line available
      if ($this->start_tab < 30) {
         $this->newPage();
      }

      $this->displayBox(0.95);

      $i = 0;
      foreach ($msgs as $msg) {
         if ($i<count($this->cols)) {
            $this->pdf->addTextWrap($this->colsx[$i]+2,$this->start_tab,$this->colsw[$i]-4,9,
                                    Toolbox::decodeFromUtf8($msg,"windows-1252"),
                                    (isset($this->align[$i]) ? $this->align[$i] : 'left'));
            $i++;
         } else {
            break;
         }
      }
      $this->start_tab -= 20;
   }


   public function displayLink ($name, $URL) {

      // New page if less than 1 line available
      if ($this->start_tab < 30) {
         $this->newPage();
      }

      $this->displayBox(0.95);
      $i = 0;

      $name = Toolbox::decodeFromUtf8($name,"windows-1252");
      $w = $this->pdf->getTextWidth(9,$name);
      $this->pdf->addLink($URL,$this->colsx[$i]+2,$this->start_tab,$this->colsx[$i]+$w+2,
                          $this->start_tab+10);
      $this->pdf->addTextWrap($this->colsx[$i]+2,$this->start_tab,$this->colsw[$i]-4,9,$name,
                              (isset($this->align[$i]) ? $this->align[$i] : 'left'));
      $this->pdf->saveState();
      $this->pdf->setLineStyle(0.5);
      $this->pdf->line($this->colsx[$i]+2,$this->start_tab-3,$this->colsx[$i]+$w+2,
                       $this->start_tab-3);
      $this->pdf->restoreState();
      $this->start_tab -= 20;
   }


   /*
    * Display a multi-line Box : 1 column only
    *
    * @param $name string display on the left, before text
    * @param $content string of text display on right (multi-line)
    * @param $minline integer for minimum box size
    * @param $maxline interger for maximum box size (1 page = 80 lines)
	 */
   public function displayText ($name, $content, $minline=3, $maxline=100) {

      // New page if less than $minline available
      if ($this->start_tab < (20+10/$minline)) {
         $this->newPage();
      }

      // The Box	Initial Size = $minline
      $gray = 0.95;
      $this->pdf->saveState();
      $this->pdf->setColor($gray,$gray,$gray);
      $this->pdf->filledRectangle(25, $bottom = $this->start_tab-$minline*10+5, $this->width-50,
                                  $minline*10+5);
      $this->pdf->restoreState();

      // Title
      $name = Toolbox::decodeFromUtf8($name,"windows-1252");
      $x = 30 + $this->pdf->getTextWidth(9, $name);
      $this->pdf->addText(27,$this->start_tab,9,$name);

      $temp = str_replace("\r\n","\n",$content);
      $lines = explode("\n", Toolbox::decodeFromUtf8($temp,"windows-1252"));
      $line = current($lines);

      // Content
      while ($line!==false && $maxline>0) {
         // Need a new page ?
         if ($this->start_tab < 30) {
            $this->newPage();
            $bottom = $this->start_tab + 10;
         }
         // Extent initial box
         if ($this->start_tab < $bottom) {
            $newbottom = $this->start_tab-5;
            $this->pdf->saveState();
            $this->pdf->setColor($gray,$gray,$gray);
            $this->pdf->filledRectangle(25, $newbottom, $this->width-50, ($bottom - $newbottom));
            $this->pdf->restoreState();
            $bottom = $newbottom;
         }
         if (!empty($line)) {
            $line = $this->pdf->addTextWrap($x,$this->start_tab,$this->width-$x-25,9,$line);
         }
         if (empty($line)) {
            $line = next($lines);
         }
         $this->start_tab -= 10;
         $maxline--;
      }
      // Final position = behind the box
      $this->start_tab = $bottom - 15;
   }


   public function displaySpace ($nb=1) {
      $this->start_tab -= ($nb * 20);
   }

   public function addPngFromFile ($image,$dst_w,$dst_h) {

      $size = GetImageSize($image);
      $src_w = $size[0];
      $src_h = $size[1];
      // Teste les dimensions tenant dans la zone
      $test_h = round(($dst_w / $src_w) * $src_h);
      $test_w = round(($dst_h / $src_h) * $src_w);

      // Teste quel redimensionnement tient dans la zone
      if ($test_h > $dst_h) {
         $pos_w = 25 + ($dst_w - $test_w) /2;
         $dst_w = $test_w;
      }
      else {
         $pos_w = 25;
         $dst_h = $test_h;
      }
      $this->start_tab -= $dst_h;
      $pos_h = $this->start_tab;
      $this->pdf->addPngFromFile($image,$pos_w,$pos_h,$dst_w,$dst_h);

   }

}

?>