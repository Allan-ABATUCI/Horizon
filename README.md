### Le concept
Une application web complète qui simplifie la gestion de projets pour les équipes. Interface intuitive et outils puissants pour planifier, suivre et livrer des projets efficacement.

**Statut** : En développement

---

### Stack technique
**Backend** : Laravel avec Inertia.js  
**Frontend** : React  
**Base de données** : sqlite  

---

### Objectifs d'apprentissage
- Développer une application fullstack avec Laravel et Inertia
- Approfondir React et les composants modernes
- Créer une architecture maintenable et évolutive
- Implémenter une expérience utilisateur fluide
- Maîtriser le déploiement d'une application complète

---

### Points techniques remarquables
- Architecture unifiée backend/frontend grâce à Inertia
- Permissions par ressource (policies) : chaque projet/tâche n'est modifiable que par son créateur, un assigné ne peut changer que le statut de sa tâche
- Système de composants React réutilisables
- Interface entièrement en français, y compris les messages de validation et la pagination côté serveur
- Plusieurs thèmes de couleurs (en plus du mode clair/sombre)
- Rappels d'échéance planifiés via le scheduler Laravel : nécessite `php artisan schedule:work` en local, ou une entrée cron `* * * * * php artisan schedule:run` en déploiement
- Recherche plein texte via SQLite FTS5 (classement par pertinence, insensible aux accents), sans dépendance externe

---

### Fonctionnalités 
- CRUD complet Projets / Tâches / Utilisateurs
- Tableau Kanban interactif avec glisser-déposer
- Calendrier de projet et gestion des échéances
- Frise (Gantt) pour visualiser la durée des tâches sur plusieurs jours
- Permissions par ressource (créateur vs assigné)
- Commentaires sur les tâches
- Notifications (assignation d'une tâche, échéance à J-1)
- Membres par projet : seuls les membres voient et interagissent avec un projet, ses tâches et ses commentaires ; le créateur gère qui rejoint
- Recherche globale (projets et tâches, raccourci Cmd/Ctrl+K)
- Pièces jointes multiples sur les tâches (10 max, 2 Mo/fichier, liste blanche de types, stockage privé)

### Prochaines étapes
- Espaces de travail multiples
- Synchronisation en temps réel
- Applications mobiles (via API)
- Intégrations tierces (Slack, Google Calendar)
