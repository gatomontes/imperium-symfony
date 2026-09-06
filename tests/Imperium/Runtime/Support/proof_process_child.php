<?php
declare(strict_types=1);
file_put_contents($argv[2], 'called\n', FILE_APPEND);
if ($argv[1] === 'read') {
    echo hash('sha256', stream_get_contents(STDIN));
    exit(0);
}
fclose(STDIN);
if ($argv[1] === 'refuse') { fwrite(STDERR, "PMA_INSTALLATION_CHECK_FAILED\n"); exit(2); }
if ($argv[1] === 'private-error') { fwrite(STDERR, 'PRIVATE_SENTINEL'); exit(7); }
exit(0);
