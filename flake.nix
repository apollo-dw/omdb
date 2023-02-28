{
  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = import nixpkgs { inherit system; };

        php = pkgs.php81.withExtensions
          ({ enabled, all }: enabled ++ (with all; [ ]));
      in rec {
        devShell = pkgs.mkShell {
          packages = (with pkgs; [ php php.packages.composer ]);
        };
      });
}
