import type { ComponentProps } from 'react';
import type { SymbolView } from 'expo-symbols';

export type AppIcon = {
  ios: string;
  android: string;
  web: string;
};

export function appIcon(icon: AppIcon): ComponentProps<typeof SymbolView>['name'] {
  return icon as ComponentProps<typeof SymbolView>['name'];
}
