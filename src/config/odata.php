<?php

return [
  // Middleware for routes
  'routes_middleware' => ['auth:api'],

  // Upload directory inside `storage` folder for files upload
  'upload_dir' => 'uploads',

  // Additional componentns for OData package discover
  'components' => [],

  // Subfolder to discover entities controllers
  'controller_subfolder' => '',

  // Flag to check permissions for dynamic calls
  'check_spatie_laravel_permissions' => false,

];
