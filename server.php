<?php

include 'server/import.php';

Import::runWithRetries('build/data.json');
