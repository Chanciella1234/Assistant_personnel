@component('mail::message')
# Bonjour {{ $name }},

Merci de vous être inscrit sur **Assistant Personnel Automatisé** !

Voici votre **code d’activation à 6 chiffres :**

@component('mail::panel')
{{ $code }}
@endcomponent

Veuillez saisir ce code dans l’application pour activer votre compte.

Merci,<br>
L’équipe Assistant Personnel Automatisé
@endcomponent
