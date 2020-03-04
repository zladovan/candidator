<?php
    require 'candidator.php';

    // Create
    //$gen = new Candidator();

    // Configure (see generator.php for all configurations, default values are listed here)
    // $gen->out_path = '/tmp/candidator/render';
    // $gen->images_root = './assets/candidates';
    // $gen->no_img_path_male = './assets/silhouette-male.png';
    // $gen->no_img_path_female = './assets/silhouette-female.png';
    // $gen->c_data_path = './assets/candidates.json';

    // Serve
    //$gen->serve();
    (new Candidator())->serve();
?>