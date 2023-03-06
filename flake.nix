{
  inputs.nixpkgs.url = "github:nixos/nixpkgs";

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = import nixpkgs { inherit system; };

        php = pkgs.php82.withExtensions
          ({ enabled, all }: enabled ++ (with all; [ pdo pdo_sqlite ]));
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
