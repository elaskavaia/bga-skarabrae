// Dojo-era GameGui internals that exist at runtime but are not in the official bga-framework.d.ts
declare const dijit: any;

interface GameGui<P extends Player = Player, G extends Gamedatas<P> = Gamedatas<P>> {
  tooltips: Record<string, any>;
  inherited(args: IArguments): any;
  clienttranslate_string(text: string): string;
}

// official Gamedatas has no index signature, game sends extra fields (tokens, counters, ...)
interface Gamedatas<P extends Player = Player> {
  [key: string]: any;
}
