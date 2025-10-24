@component('mail::message')
# 🔔 Rappel d’activité à venir

Ceci est un petit rappel de votre application **LifePlanner** 🧠  
Votre activité approche à grands pas ⏰ :

@component('mail::panel')
<h2 style="text-align:center; color:#2563eb; margin:0;">{{ strtoupper($activite->titre) }}</h2>
<p style="text-align:center; margin:5px 0; font-size:14px; color:#374151;">
{{ \Carbon\Carbon::parse($activite->date_debut_activite)->format('d/m/Y à H:i') }}
</p>
<p style="text-align:center; color:#6b7280; margin:0;">
Priorité : <strong style="color:
@if($activite->priorite == 'forte') #dc2626 
@elseif($activite->priorite == 'moyenne') #f59e0b 
@else #16a34a @endif;">
{{ ucfirst($activite->priorite) }}
</strong>
</p>
@endcomponent

📅 **Message de rappel :**  
{{ $messageAlerte }}


Merci pour votre confiance 💙  
**L’équipe LifePlanner**

<hr style="border:none; border-top:1px solid #e5e7eb; margin-top:30px;">
@endcomponent
