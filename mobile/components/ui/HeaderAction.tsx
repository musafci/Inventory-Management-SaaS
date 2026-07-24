import { type Href } from 'expo-router';

import { Button } from './Button';

type HeaderActionProps = {
  label: string;
  href: Href;
};

export function HeaderAction({ label, href }: HeaderActionProps) {
  return (
    <Button
      href={href}
      label={label}
      size="compact"
      style={{ alignSelf: 'auto', marginRight: 4 }}
      variant="primary"
    />
  );
}
