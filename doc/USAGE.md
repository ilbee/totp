# Usage

<a name="prerequisites"></a>
## Prerequisites
This library is designed to be used with the Symfony framework.
You need to have a Symfony project with a user entity that implements the `UserInterface` interface.
Your User entity must implement a `getTotpSecret()` method that returns the secret key known by server.

```php
<?php
// ./src/Entity/User.php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    public function getTotpSecret(): string
    {
        return $this->totpSecret;
    }
}
```

<a name="generate_a_secret_key"></a>
## Generate a secret key
To generate a secret key, you can use the `generateSecret()` static method.
You have to pass a Symfony `UserInterface` object as an argument.

```php
<?php
// ./src/Controller/UserController.php

namespace App\Controller;

use Ilbee\Totp\Totp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbrstractController
{
    #[Route('/user/generate/secret', name: 'app_user_generate_secret')]
    public function generateSecret(): Response
    {
        $secret = Totp::generateSecret($this->getUser());
        
        return $this->render('user/secret.html.twig', [
            'secret' => $secret,
        ]);
    }
}
```

<a name="validate_a_totp"></a>
## Validate a TOTP
To validate a TOTP, you have to create an `Totp` instance with user secret key, and use the `validate()` method.

```php
<?php
// ./src/Controller/UserController.php

namespace App\Controller;

use Ilbee\Totp\Totp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbrstractController
{
    #[Route('/user/validate/totp', name: 'app_user_validate_totp')]
    public function validateTotp(Request $request): Response
    {
        $inputTotp = $request->request->get('totp');
        $totp = new Totp($this->getUser()->getTotpSecret());
        
        if ($inputTotp) {
            $result = 'TOTP Invalid !';
            if ($totp->validate($inputTotp)) {
                $result = 'TOTP Valid !';
            }
        
            return $this->render('user/validate_result.html.twig', [
                'message' => $result,
            ]);
        }

        return $this->render('user/validate_form.html.twig');
    }
}
```

```twig
{# ./templates/user/validate_result.html.twig #}

<h1>Validation TOTP</h1>
<p>{{ message }}</p>
```

```twig
{# ./templates/user/validate_form.html.twig #}

<h1>Validation TOTP</h1>
<form action="{{ path('app_user_validate_totp') }}" method="post">
    <label for="totp">TOTP</label>
    <input type="text" name="totp" id="totp" required>
    <button type="submit">Valider</button>
</form>
```

<a name="generate_a_qr_code"></a>
## Generate a QR code
If you want to generate a QR code for the secret key, you have to install library [endroid\qr-code-bundle](https://github.com/endroid/qr-code-bundle).
Then you can use the generate the QR Code.

```php
<?php
// ./src/Controller/UserController.php

namespace App\Controller;

use Ilbee\Totp\Totp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbrstractController
{
    #[Route('/user/generate/secret', name: 'app_user_generate_secret')]
    public function generateSecretQrCode(): Response
    {
        $secret = Totp::generateSecret($this->getUser());       
        $uri = $totp->getUri('My secure application', $this->getUser());
        
        return $this->render('user/secret_qrcode.html.twig', [
            'secret' => $secret,
            'uri'    => $uri,
        ]);
    }
}
```

```twig
{# ./templates/user/secret_qrcode.html.twig #}

<h1>Secret key: {{ secret }}</h1>
<img src="{{ qr_code_url(uri) }}" alt="QR Code" />
```