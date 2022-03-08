<?php

return function(Psr\Container\ContainerInterface $container) {
    return 'foo' . get_class($container);
};