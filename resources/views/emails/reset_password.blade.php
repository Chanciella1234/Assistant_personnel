@component('mail::message')
# Bonjour {{ $name }},

Vous avez demandé à réinitialiser votre mot de passe.

Voici votre **code de réinitialisation** :

@component('mail::panel')
{{ $code }}
@endcomponent

Ce code est valable pendant **15 minutes**.

Si vous n'avez pas demandé cette action, ignorez simplement ce message.

Merci,<br>
L’équipe Assistant Personnel Automatisé
@endcomponent
