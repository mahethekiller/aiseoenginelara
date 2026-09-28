# Module Architecture Blueprint

Every new module added to the project must follow this file hierarchy:

```
[ModuleName]/
├── Routes/
│   ├── web.php
│   └── api.php
├── Controllers/
│   ├── Web/[ModuleName]Controller.php
│   └── Api/V1/[ModuleName]ApiController.php
├── Services/
│   └── [ModuleName]Service.php
├── Repositories/
│   └── [ModuleName]Repository.php
├── Requests/
│   ├── Store[ModuleName]Request.php
│   └── Update[ModuleName]Request.php
├── Policies/
│   └── [ModuleName]Policy.php
└── Resources/V1/
    ├── [ModuleName]Resource.php
    └── [ModuleName]Collection.php
```