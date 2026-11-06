<?php
// Prevent directory listing or direct folder access
http_response_code(403);
exit('Access Denied');
