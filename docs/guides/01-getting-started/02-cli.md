# 2. Installer la CLI

Clonez la CLI officielle sur votre machine, dans un dossier que vous conserverez en dehors de vos projets :

~~~bash
mkdir -p "$HOME/.local/bin"
git clone https://github.com/Symsvel-Framework/symsvel-cli.git "$HOME/.local/symsvel-cli"
ln -s "$HOME/.local/symsvel-cli/bin/symsvel" "$HOME/.local/bin/symsvel"
export PATH="$HOME/.local/bin:$PATH"
symsvel doctor
~~~

Ajoutez la ligne export à la configuration de votre shell (par exemple ~/.zshrc ou ~/.bashrc) pour rendre la commande disponible dans les nouveaux terminaux.

Si doctor signale un prérequis manquant, installez-le puis relancez la commande. La CLI Symfony est facultative.

Pour comprendre les commandes et les responsabilités de la CLI, consultez la [référence CLI](../06-reference/01-cli.md).

[Étape suivante : créer un projet](03-creer-un-projet.md).
