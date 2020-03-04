<?php
    require "lib/imageSmoothArc.php";

    /**
     * Generator for images with candidates.
     * 
     * Simplest usage:
     * ```
     * <?php
     *      $gen = new Candidator();
     *      $gen->serve();
     * ?>
     * ```
     * 
     * Some custom configuration:
     * ```
     * <?php
     *      $gen = new Candidator();
     *      
     *      $gen->candidates_count = 150;
     *      $gen->images_root = 'http://example.com/images';
     *      $gen->background_img_path =  './assets/my-background.png';
     *      $gen->c_data_path = './assets/my-candidates.json';
     *      $gen->number_color = array(48, 56, 65);
     * 
     *      $gen->serve();
     * ?>
     * ```
     */
    class Candidator {
        
        /** How many candidates are there in total */
        public $candidates_count = 18;

        /** How many candidates can be maximally drawn */
        public $max_candidates = 4;

        /** Path to font used to draw numbers (need to be local path) */
        public $font_path = './assets/junegull.ttf';

        /** Root path which will be prepend to each candidate image path (can be local or url) */
        public $images_root = './assets/candidates';

        /** Path to image used when male candidate does not have image (can be local or url) */
        public $no_img_path_male = './assets/silhouette-male.png';

        /** Path to image used when female candidate does not have image (can be local or url) */
        public $no_img_path_female = './assets/silhouette-female.png';

        /** Path to image used as background (need to be PNG for now) */
        public $background_img_path = './assets/background.png';

        /** Path to JSON with candidates data */
        public $c_data_path = './assets/candidates.json';

        /** Candidate image will be resized to original size divided by this number */
        public $c_scale_down = 2.6;

        /** Rectangle which will be copied from down scaled candidate image, array wih 4 numbers (x,y,width,height) */
        public $c_rect = array(48, 0, 300, 295);

        /** Bottom padding for all candidates */
        public $c_padding_bottom = 87;

        /** Color used to draw candidate number (array with 3 values = r, g, b) */
        public $number_color = array(48, 56, 65);

        /** Font size used to draw number */
        public $number_size = 24;

        /** Size of stroke around candidate number, 0 for no stroke */
        public $number_stroke_size = 0;
        
        /** Color used to draw stroke around candidate number (array with 3 values = r, g, b) */
        public $number_stroke_color = array(0, 173, 181);

        /** Color used to draw circle under candidate number (array with 3 values = r, g, b) */
        public $circle_color = array(255, 255, 255);

        /** Diameter of circle under candidate number */
        public $circle_size = 74;

        /** Top padding from candidate rect for circle with number */
        public $circle_padding_top = 16;

        /** Right padding from candidate rect for circle with number */
        public $circle_padding_right = 15;

        /** Bottom message used for one candidate */
        public $message_singular = "This is my vote for the change";

        /** Bottom message used for multiple candidates, you can use {count} as placeholder for number of candidates */
        public $message_plural = "These are my {count} votes for the change";

        /** Color used to draw message at the bottom */
        public $message_color = array(255, 255, 255);

        /** Font size used to draw bottom message */
        public $message_size = 32;

        /** Bottom padding for bottom message */
        public $message_padding_bottom = 25;

        /** Path where images should be saved and where to look if image isn't already generated */
        public $out_path = '/tmp/candidator/render';

        /**
         * Generates image with candidates.
         * 
         * How it works in general:
         *  
         *  - background image is loaded
         *  - each candidate image is loaded
         *  - each candidate image is drawn re-sampled on precalculated position to background image
         *  - circle with number is drawn next to each candidate
         *  - bottom message is drawn
         */
        function generate($numbers) {
            // Get size of background image which will be used as result image size
            $info = getimagesize($this->background_img_path);
            $width = $info[0];
            $height = $info[1];

            // Load background image
            $image = $this->load_image($this->background_img_path);

            // Get count of candidates with given maximum
            $count = min(sizeof($numbers), $this->max_candidates);
            
            // Load all candidates
            $c_data = json_decode(file_get_contents($this->c_data_path));

            // Set colors for number
            $number_color = imagecolorallocate($image, $this->number_color[0],$this->number_color[1],$this->number_color[2]);
            $number_stroke_color = imagecolorallocate($image, $this->number_stroke_color[0],$this->number_stroke_color[1],$this->number_stroke_color[2]);
            
            // Calculate shift from right to always center candidates
            $shift_x = $width / 2 - ($this->c_rect[2] * $count * 0.5);

            // Calculate source rect which will be copied from candidate image in original size
            $c_rect_src = array_map(function($x) { return $x * $this->c_scale_down; }, $this->c_rect);
            
            // Draw each candidate
            for ($i = 0; $i < $count; $i++) {
                // Pick up candidate by its `number`
                foreach ($c_data as $x) {
                    if ($x->number == $numbers[$i]) {
                        $c = $x;
                    }
                }

                // Calculate position of candidate
                $c_pos_x = $shift_x + $i * $this->c_rect[2];
                $c_pos_y = $height - $this->c_padding_bottom - $this->c_rect[3];

                // Load image with candidate
                $c_img = empty($c->img) ? $this->load_image($c->gender == 'm' ? $this->no_img_path_male : $this->no_img_path_female) : $this->load_image($this->images_root . '/' . $c->img);
                
                // Draw scaled candidate on background
                imagecopyresampled($image, $c_img, $c_pos_x, $c_pos_y, $c_rect_src[0], $c_rect_src[1], $this->c_rect[2], $this->c_rect[3], $c_rect_src[2], $c_rect_src[3]);

                // Set number circle location
                $nc_pos_x = $c_pos_x + $this->c_rect[2] - $this->circle_size / 2 - $this->circle_padding_right;
                $nc_pos_y = $c_pos_y + $this->circle_size / 2 + $this->circle_padding_top;
                
                // Get width and height of number text to be able to center it
                list($n_width, $n_height) = $this->text_size($this->number_size, strval($c->number));
                
                // Calculate position of number in the center of circle
                // + 2 is correction because of "bold effect" used
                $n_pos_x = $nc_pos_x - $n_width / 2 + 2;
                $n_pos_y = $nc_pos_y + $n_height / 2 + 2;
                
                // Draw circle under number
                imageSmoothArc($image, $nc_pos_x, $nc_pos_y, $this->circle_size, $this->circle_size, array($this->circle_color[0], $this->circle_color[1], $this->circle_color[2], 0), 0, 2*M_PI);

                // Draw number
                $this->imagettfstroketext($image , $this->number_size, 0, $n_pos_x, $n_pos_y, $number_color, $number_stroke_color, $this->font_path, strval($c->number), $this->number_stroke_size);
                
                // Cleanup
                imagedestroy($c_img); 
            }

            // Build text of message, use singular or plural version depending on candidates count
            $message = $count == 1 ? $this->message_singular : $this->message_plural;
            $message = str_replace("{count}", $count, $message);
            
            // Set color for message
            $message_color = imagecolorallocate($image, $this->message_color[0],$this->message_color[1],$this->message_color[2]);
            
            // Get size of message to be able to center it
            list($m_width, $m_height) = $this->text_size($this->message_size, $message);

            // Calculate position of bottom message 
            $message_pos_x = $width / 2 - $m_width / 2;
            $message_pos_y = $height - $this->message_padding_bottom;

            // Draw bottom message
            imagettftext($image , $this->message_size, 0, $message_pos_x, $message_pos_y, $message_color, $this->font_path, $message);
            
            return $image;
        }

        /**
         * Loads image if it was generated before or generate and save new one.
         * 
         * If `force_generate` is set to `true` images will be always generated and old images will be overwritten.
         */
        function load_or_generate($numbers, $force_generate = false) {
            // Create path for image
            $path = $this->out_path . '/' . implode('-', $numbers) . '.png';

            // Load if exists and we do not want to force recreate
            if (file_exists($path) && !$force_generate) {
                return $this->load_image($path);
            }
            
            // Else generate and store new image
            $image = $this->generate($numbers);
            if (!is_dir($this->out_path)) {
                mkdir($this->out_path, 0777, true);
            }
            imagepng($image, $path); 

            return $image;
        }

        /** 
         * Parse numbers of candidates.
         * 
         * Input should be numbers delimited by `-`, e.g. `1-2-3`.
         * All numbers greater then `max_candidates` count are removed.
         * All non numeric characters are removed.
         */
        function parse_numbers($numbers_raw) {
            $numbers = array_values(array_filter(explode('-', $numbers_raw), function ($x) { 
                return !empty($x) and is_numeric($x) and (int)$x <= 150; 
            }));
            return array_slice($numbers, 0, $this->max_candidates); 
        }

        /**
         * Serve image with candidates.
         * 
         * Serve reads numbers of candidates from request parameter.
         * Name of parameter is by default `nrs` and can be changed via `param_name` argument.
         * Then it loads or generates image and outputs as `image/png` content.
         * 
         * It also accepts optional request parameter `force` to always generate images (even when they exists already).
         */
        function serve($param_name = 'nrs', $force_param_name = 'force') {

            // Validate param is set
            if (!isset($_REQUEST[$param_name])) {
                http_response_code(400);
                echo("Missing parameter: " . $param_name);
                die();
            }

            // Parse numbers from param
            $numbers = $this->parse_numbers($_REQUEST[$param_name]);
                
            // Validate there are some numbers to process
            if (empty($numbers)) {
                http_response_code(400);
                echo("Invalid parameter: " . $param_name);
                die();
            }

            // Get optional force parameter
            $force = isset($_REQUEST[$force_param_name]) ? $_REQUEST[$force_param_name] : false;

            // Get image
            $image = $this->load_or_generate($numbers, $force);

             // Output as png image
            header("Content-Type: image/png"); 
            imagepng($image); 

            // Cleanup
            imagedestroy($image); 
        }

        private function load_image($path) {
            if (substr_compare($path, "png", -strlen("png")) === 0) {
                return imagecreatefrompng($path);
            } else {
                return imagecreatefromjpeg($path);
            }
            
        }
        
        private function text_size($font_size, $text) {
            $box = imagettfbbox($font_size, 0, $this->font_path,  $text); 
            $width = abs($box[0] - $box[2]);
            $height = abs($box[0] - $box[7]);
            return array($width, $height);
        }

        /**
         * Draw text with outline
         * 
         * Use same color for outline to create `bold effect`
         * Taken from: https://stackoverflow.com/a/46662045/9008503
         */
        private function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px) {
            for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
                for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
                    $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
            return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
        }
    }
?>