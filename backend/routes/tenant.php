<?php

/*
| Scholar ERP identifies the school from the `X-Tenant` header, so every
| school route lives in routes/api.php (see IdentifySchool). This file
| replaces the domain-based sample that `tenancy:install` generates, whose
| catch-all `GET /` would otherwise shadow the app's home page.
*/
