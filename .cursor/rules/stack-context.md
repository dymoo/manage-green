---
description:
globs:
alwaysApply: true
---

## context on our stack

-   laravel v12 [https://laravel.com/docs/12.x/](mdc:laravel v12 docs)
-   filament for panel building [https://filamentphp.com/docs](mdc:filament documentation)
-   liveview
-   tailwindcss / flux (default to flux components when you can)
-   spatie/laravel-permission for permissions
-   multitennancy through filaments built in multitennancy set up [https://filamentphp.com/docs/3.x/panels/tenancy](mdc:filament multitenancy docs)
-   deployed to digitalocean app platform with postgresql v17, we want to keep our deployment costs as low as possible.
-   we are running postgres in prod and sqlite in dev.
-   We are running in a machine with 512mb of ram, when you are doing something that may run into ram constraints, please notify me and suggest an alternative.
