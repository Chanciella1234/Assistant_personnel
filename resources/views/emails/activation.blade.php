@component('mail::message')
# 👋 Bonjour {{ $name }},

Merci de vous être inscrit sur **LifePlanner** — votre nouvel assistant personnel automatisé 💡  

Nous sommes ravis de vous accueillir parmi nous !  
Avant de commencer à planifier vos activités, veuillez **confirmer votre compte** avec le code ci-dessous :

@component('mail::panel')
<h2 style="text-align:center; font-size: 28px; letter-spacing: 4px; margin:10px 0; color:#2563eb;">
    {{ $code }}
</h2>
@endcomponent

👉 **Ce code est valable pendant 30 minutes.**  
Saisissez-le dans l’application pour finaliser votre inscription.



Merci pour votre confiance 💙  
**L’équipe LifePlanner**

<hr style="border:none; border-top:1px solid #e5e7eb; margin-top:30px;">

@endcomponent
