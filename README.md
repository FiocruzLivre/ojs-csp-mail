# CSP Mail Plugin

Plugin to customize the email sending in Open Journal System (OJS) according to Cadernos de Saúde Pública da Fiocruz requirements.


**OJS version: 3.4.0, 3.5.0**


## Installation

**OJS 3.5 (ensp-csp-ojs-3.5 stack):** this repo is bind-mounted into the
`app` container at `/var/www/html/plugins/generic/cspMail` via
`docker-compose.yml` in the `ensp-csp-ojs-3.5` repo. Enable it in
_Website > Plugins_ after the container picks up the mount.

**Standalone OJS install (3.4 or earlier):**

1) Clone this repo inside the directory ``ojs/plugins/generic/`` :

   ``git clone https://github.com/FiocruzLivre/ojs-csp-mail.git cspMail``

    > The plugin must be inside a _cspMail_ named folder
2) In the system, enable the plugin in _Website > Plugins_ area
