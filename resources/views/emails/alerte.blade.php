@component('mail::message')
# 🔔 Rappel d’activité

{{ $messageAlerte }}

**Titre :** {{ $activite->titre }}  
**Date de début :** {{ \Carbon\Carbon::parse($activite->date_debut_activite)->format('d/m/Y H:i') }}  
**Priorité :** {{ ucfirst($activite->priorite) }}

@component('mail::button', ['url' => 'https://votre-application.com'])
Voir mes activités
@endcomponent

Merci,  
L’équipe LifePlanner.
@endcomponent
