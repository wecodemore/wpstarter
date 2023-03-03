<?php

/*
What you do when a CLI tool uses regexp to parse some source code file to get the lines to then
pass to eval()?
Well, you sing "ob-la-di, ob-la-da life goes on, brah"

Desmond takes a trolley to the jeweler's store
Buys a 20-carat golden ring (ring)
Takes it back to Molly waiting at the door
And as he gives it to her, she begins to sing (sing)
Ob-la-di, ob-la-da (la, la, la, la, la, la)
Life goes on, brah (la, la, la, la, la, la)
La, la, how the life goes on.

require wp-settings.php
 */

const WP_STARTER_WP_CONFIG_PATH = __DIR__ . '{{{WP_CONFIG_PATH}}}';

require realpath(__DIR__ . '{{{WP_CONFIG_PATH}}}');
