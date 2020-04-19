---
date: "2020-04-19 10:00:00"
tags: ["git"]
title: "Configurer son serveur Git avec Ansible"
description: "test"
language: fr
---

Git est capable de fonctionner en mode serveur sur n'importe quelle machine : c'est à dire se comporter comme un "remote repository" (dépot distant) et accepter les actions pull, push et clone d'une autre machine.

C'est ce que font pour nous des plateformes comme *GitHub* et *GitLab* en nous fournissant une adresse SSH avec laquelle nous pouvons executer :

`git clone git@github.com:Moi/MonProjet.git`.

Mais nous pouvons tout à fait héberger notre propre serveur git et avoir un dépot distant à notre nom, sur lequels nous pourrons push et pull en ligne de commande comme nous le faisons avec ces services :

`git clone git@mon-domaine.fr:mon-projet.git`

Cela permet d'avoir des dépots privés qui ne dépendent pas de la disponibilité de services externes.
Nous pourrons par exemple y versionner des contenus privés de manière illimité ou encore dupliquer nos projets sur ces remote secondaires comme copie de sauvegarde.

La [documentation Git](https://git-scm.com/book/fr/v2/Git-sur-le-serveur-Mise-en-place-du-serveur) est assez claire et nous explique comment mettre un serveur en place manuellement.

Moi, je provisionne mon serveur avec Ansible, aussi je vous propose dans cet article d'écrire un rôle Ansible qui configurera pour nous ce serveur git auto-hebergé.

## Création d'un rôle "git"

Nous créons un simple [rôle Ansible](https://docs.ansible.com/ansible/latest/user_guide/playbooks_reuse_roles.html) appelé `git` :

```
roles/git/
    defaults/
        main.yml
    tasks/
        main.yml
```

Il contient un fichier décrivant les taches `tasks/main.yml` et un fichier décrivant les variables par défaut: `defaults/main.yml`.

Nous allons maintenant décrire chaque étape de la création d'un serveur git sous la forme d'une tache Ansible dans notre rôle.

### Créer un utilisateur git

Premièrement, nous créons un utilisateur `git` en ajoutant une première tache dans le fichier `roles/git/tasks/main.yml` :

```yaml
- name: Create the Git user
  user:
    name: git
    shell: /usr/bin/git-shell
```

Note: le shell `git-shell` est fournit par Git et permet de refuser l'accès SSH en CLI.

C'est ce qui se passe lorsque l'on essaye de se [connecter à Github en SSH](https://help.github.com/en/github/authenticating-to-github/testing-your-ssh-connection) :

```shell
$ ssh -T git@github.com
Hi Tom32i! You've successfully authenticated, but GitHub does not provide shell access.
```

### Ajouter les clé publiques autorisées

Nous ne voulons pas autoriser n'importe qui à accéder à nos dépots (sinon ils ne seraient pas privés).
C'est pourquoi nous allons renseigner les clés SSH autorisées à se connecter :

Pour cela nous définissons d'abord une variable de rôle dans `roles/git/defaults/main.yml`, chargée de regroupée les chemins vers les clés publiques autorisées :

```yaml
git_authorized_keys: []
```

Puis nous ajoutons une seconde tache dans `roles/git/tasks/main.yml` chargée de boucler sur ce tableau et d'ajouter chaque clé à la liste des clé autorisées pour l'utilisateur git :

```yaml
# ...

- name: Set authorized key for Git user
  loop: "{{ git_authorized_keys | default([]) }}"
  authorized_key:
    user: git
    state: present
    key: "{{ lookup('file', item) }}"
    key_options: "no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty"
```

Nous n'aurons plus qu'a renseigner dans notre provisionning la liste de nos clée publiques autorisée, comme ceci :

```yaml
git_authorized_keys:
  - "{{ playbook_dir }}/files/ssh_keys/ma_clee_rsa.pub"
```

(ici je choisi de stocker mes clées publiques sous forme de fichiers dans mon playbook)

### Création des dépots Git

Nous continuons de suivre le protocol décrit par la documentation Git en créant nos dépots "vides".

Pour cela nous allons définir dans `roles/git/defaults/main.yml` deux nouvelels variable de rôle :
- `git_home` : le chemin raçine vers nos dépots.
- `git_repositories` : la liste des dépots à créer.

```yaml
git_home: /home/git/
git_repositories: []
```

Puis nous créons pulsieurs nouvelles taches dans `roles/git/tasks/main.yml` :

#### Création des dossiers accueillant les dépots

On boucle sur `git_repositories` et créons pour chaque entrée un dossier dans le repartoire racine :

```yaml
# ...

- name: Create git directories
  loop: "{{ git_repositories | default([]) }}"
  file:
    path: "{{ git_home }}{{ item }}.git"
    state: directory
    owner: git
```

Ainsi la configuration `git_repositories: [mon-projet]` donnera l'arborescence `/home/git/mon-projet.git/`.

#### Initialisation des dépots

Puis nous initialisons un dépot git vide dans chacun de ces dossiers avec la commande `git init --bare` :

```yaml
# ...

- name: Init git dépots
  loop: "{{ git_repositories | default([]) }}"
  command: git init --bare
  args:
    chdir: "{{ git_home }}{{ item }}.git"
```

#### Permissions

Enfin nous définissons l'utilisateur git comme propriétaire du repertoire raçine et de tous ses enfants, récursivement :

```yaml
# ...

- name: Change file ownership, group and permissions
  file:
    path: "{{ git_home }}"
    state: directory
    recurse: yes
    owner: git
    group: git
```

Nous nous assurons ainsi que l'utilisateur git aura les droits suffisants pour fonctionner.

## Usilisation du rôle "git" dans un playbook

Notre rôle est prêt ! Nous n'avons plus qu'a l'utiliser dans un playbook.

Je défini un playbook `playbooks/git.yml` qui utilise notre rôle `git` et s'applique à tout les hosts :

```yaml
- hosts: all
  become: true
  roles:
    - git
  vars:
    git_authorized_keys:
        - "{{ playbook_dir }}/files/ssh_keys/ma_clee_rsa.pub"
    git_repositories:
        - mon-projet
```

Je renseigne comme variables :
- Ma clée publique que j'ai pris soin de placer dans `playbooks/files/ssh_keys/` au préalable.
- La liste des dépots privés que je souhaite

Je peux maintenant provisier mon serveur en éxecutant mon playbook :

```shell
ansible-playbook playbooks/git.yml
```

## Résultat

Maintenant que notre serveur est configuré avec Ansible, nous pouvons utiliser notre dépot git privé !

L'adresse SSH de notre dépot privé est au format suivant :

`[user]@[host]:[path/to/repository]`.

Dans notre cas, avec la configuration ci dessus cela donne :

`git@mon-domaine.fr:/home/git/mon-projet.git`

Et puisque nous avons placé nos dossiers de dépot dans le dossier de l'utilisateur git `/home/git`, nous pouvons utiliser le chemin raccourci :

`git@mon-domaine.fr:mon-projet.git`

_Note :_ Ici nous considérons que `mon-domaine.fr` pointe vers l'ip de notre serveur. Mais cela fonctionnera de la même manière avec une adresse IP ou un hostname ssh local.

Nous pouvons utiliser cette adresse pour définir un nouveau dépot distant pour notre projet existant :

```shell
$ cd ~/projets/mon-projet
$ git remote add perso git@mon-domaine.fr:mon-projet.git
$ git push -u perso master
> ...
> To git@mon-domaine.fr:mon-projet.git
>  * [new branch]      master -> master
> Branch 'master' set up to track remote branch 'master' from 'perso'.
```

Et le cloner sur une autre machine :

```shell
$ cd ~/projets
$ git clone git@mon-domaine.fr:mon-projet.git
> Cloning into 'mon-projet'...
```

C'est fait ! Nous avons maintenant une copie privée de notre code :)
