<?php

include __DIR__ . '/../vendor/autoload.php';

$bool = \Leaf\Image::thumbCut('./cat.png', './cat_mm.png', 100, 100);

