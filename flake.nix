{
  inputs.nixpkgs.url = "github:nixos/nixpkgs";

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = import nixpkgs { inherit system; };

        php = pkgs.php82.buildEnv {
          extensions =
            ({ enabled, all }: enabled ++ (with all; [ pdo pdo_sqlite ]));
          extraConfig = ''
            opcache.enable=1
            opcache.enable_cli=1
            opcache.jit_buffer_size=100M
            opcache.memory_consumption=256
            opcache.interned_strings_buffer=8
          '';
        };
      in rec {
        devShell = pkgs.mkShell {
          packages = (with pkgs; [
            mdbook
            php
            php.packages.composer
            php.packages.php-cs-fixer
            sqlite
            gitAndTools.gh
            rlwrap
          ]);
        };
      });
}
