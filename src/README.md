## Place folders with required assets/libraries/projects here

Every sibling folder of `NanoCDS/` becomes a project in the web UI.

    assets/
      .htaccess
      NanoCDS/      <- the application, never listed
      bootstrap/
        5.3.3/
          bootstrap.min.css
      jquery/
        3.7.1/
          jquery.min.js

- The first path segment is the asset name, a version-looking segment is the version.
- Name a folder `__hidden` to hide it from nanoCDS completely.
- Anything starting with a dot (`.git`, `.env`) is invisible and unservable.

See the [README in the repository root](../README.md) for URLs, the JSON API and configuration.
