@component('mail::message')

Vous avez demandé à changer votre adresse e-mail associée à votre compte **LifePlanner**.  

Veuillez utiliser le code de vérification ci-dessous pour confirmer cette action :

@component('mail::panel')
<h2 style="text-align:center; font-size: 28px; letter-spacing: 4px; margin:10px 0; color:#000;">
    {{ $code }}
</h2>
@endcomponent

👉 <strong>Ce code est valable pendant 15 minutes.</strong>

<hr style="border:none; border-top:1px solid #e5e7eb; margin:20px 0;">

Merci pour votre confiance 🙏  
**<span style="color:#000;">L’équipe LifePlanner</span>**
@endcomponent
