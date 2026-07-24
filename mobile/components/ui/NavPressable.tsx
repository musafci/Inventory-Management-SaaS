import { useRouter, type Href } from 'expo-router';
import { forwardRef } from 'react';
import type { PressableProps } from 'react-native';

import { AnimatedPressable } from './AnimatedPressable';

type NavPressableProps = Omit<PressableProps, 'onPress'> & {
  href: Href;
  scaleTo?: number;
  onPress?: PressableProps['onPress'];
};

export const NavPressable = forwardRef<React.ComponentRef<typeof AnimatedPressable>, NavPressableProps>(
  function NavPressable({ href, onPress, ...props }, ref) {
    const router = useRouter();

    return (
      <AnimatedPressable
        {...props}
        ref={ref}
        onPress={(event) => {
          onPress?.(event);
          router.push(href);
        }}
      />
    );
  },
);
