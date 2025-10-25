@component('mail::message')
# 🔐 Réinitialisation de votre mot de passe

Bonjour ,  

Vous avez demandé à **réinitialiser votre mot de passe** sur **LifePlanner**.  
Veuillez utiliser le code ci-dessous pour finaliser la procédure :

@component('mail::panel')
<h2 style="text-align:center; font-size: 28px; letter-spacing: 4px; margin:10px 0; color:#2563eb;">
    {{ $code }}
</h2>
@endcomponent

⏳ **Ce code est valable pendant 30 minutes.**  
Saisissez-le dans l’application pour définir un **nouveau mot de passe sécurisé**.

Merci pour votre confiance 💙  
**L’équipe LifePlanner**

<hr style="border:none; border-top:1px solid #e5e7eb; margin-top:30px;">

<small style="color:#6b7280; display:block; text-align:center;">
Besoin d’aide ? Contactez-nous via le support LifePlanner.
</small>
@endcomponent
