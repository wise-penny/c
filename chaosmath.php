    <?php

    class shuriken
    {
        public $maps;
        public $markers;
        public $path_cntr;
        public $line = "";
        public $map_iter;
        public $dictionary = [];

        function __construct($filename, $out)
        {
        
            $fin = fopen("$filename", "r");
            $fout = fopen("$out","w");

            $left = 0;

            $str = "";
            $compressed_rate = 0;
            while (filesize("$filename") > $this->map_iter)
            {
                $this->maps = str_split(fread($fin,3));
    
                $this->map_iter += count($this->maps);
    
                $this->decodePath();
    
                if ($this->map_iter%140000 == 0)
                {
                    $this->aggregateBits($filename, $fout);
                }
            }

            $this->aggregateBits($filename, $fout);
            echo "\n";
            fclose($fin);
            fclose($fout); 
        }

        public function decodePath()
        {
            $bin = 0;
            $rep = "";
            $h = 0;
            foreach ($this->maps as $key)
            {
                $b = 0;
                $rep .= $key;
                if (strlen($rep) != count($this->maps) && strlen($rep) < 4)
                {
                    continue;
                }
                while (strlen($rep) > $b)
                {
                    $bin <<= 8;
                    $bin += ord($rep[$b]);
                    $b++;
                }
                $rep = "";
                $check_bin = $bin >> 8;
                $first_7 = $bin%pow(2,8);
                //$this->line .= str_repeat("0",3-strlen(decbin(abs($bin%8)))) . decbin(abs($bin%8));
                //$bin >>= 8;
                
                do
                {
                    // This is greater than 256, so we add seven
                    // and move over 8, likewise vice versa but subtract
                    //$this->line .= ($bin >> 8 > 256) ? "1" : "0";
                    $bin -= (pow(2,8) - 1);
                    $bin = ($bin >> 8);
                } while (255 < $bin);
                $nibbles = $bin;
                
                $h = 0;
                
                for ( ; $h < 256 && abs($check_bin - $nibbles) < 255 ; $h++)
                {
                    for ($j = 0 ; $j < $h && abs($check_bin - $nibbles) < 255 ; $j++)
                    { 
                        $nibbles += (1 << abs($j));
                        
                        if (abs($check_bin - $nibbles) > 256)
                        {
                            $this->line .= chr($h). "h";
                            $this->line .= chr($j). "j";
                            $this->line .= chr($check_bin - $nibbles). "n";
                            return;
                        }
                    }
                    $nibbles >>= 1;
                }
                $this->line .= implode($this->maps) . "x";
            }
        }

        public function encodePath($filename)
        {
        
            $info = fopen($filename, "r");
            $outfo = fopen($filename.".xiv", "w");
            $decbin = "";
            $this->maps = fread($info, filesize($filename));
            foreach (str_split($this->maps,1) as $kv)
            {
                $decbin .= decbin(ord($kv));
            }

            while (strlen($decbin) > 0)
            {
                $bin1s = ltrim('1',$decbin);
                $last = substr($decbin,0,8);
                $x = strlen($bin1s);
                $last = bindec($last);

                for ($i = 0 ; $i < strlen($decbin) - $x ; $i++)
                {
                    $last <<= 7;
                    $last = (($last + 127));
                }
                $last >>= 8;
                $string = "";
                while ($last > 0)
                {
                    $byte = $last%256;
                    $string .= chr($byte);
                    $last = ($last >> 8);
                }
                fwrite($outfo,$string);
                $decbin = substr($decbin, -$x - 8);
            }
            fclose($outfo);
            fclose($info);
        }

        public function aggregateBits($filename, $fout)
        {
            $byte_array = str_split($this->line,8);

            $this->line = "";
            $str = "";

            foreach ($byte_array as $kv)
            {
                $str .= chr(bindec($kv));
            }
            $this->path_cntr += strlen($str);
            $this->display($filename);
            fwrite($fout,$str);
         }
     
         public function display($filename)
         {
            echo "Percent done: " . round($this->map_iter/filesize("$filename"),3)*100 . " %    \t\t\t";
            echo "Compression Rate: " . round($this->path_cntr/$this->map_iter,3)*100 . " %    \r";
         }
    }

    $x = new shuriken($argv[1], $argv[2]);
    $y = new shuriken($argv[2], "out1.file");
    $y = new shuriken("out1.file","out2.file");

    ?>
