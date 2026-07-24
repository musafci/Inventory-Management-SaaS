import { useEffect, useRef, useState } from 'react';
import { Animated, Text, type TextProps } from 'react-native';

import { theme } from '@/src/theme';

type AnimatedCounterProps = TextProps & {
  value: number;
  duration?: number;
};

export function AnimatedCounter({ value, duration = 600, style, ...props }: AnimatedCounterProps) {
  const [display, setDisplay] = useState(0);
  const animated = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    animated.setValue(0);
    const listenerId = animated.addListener(({ value: v }) => {
      setDisplay(Math.round(v));
    });

    const anim = Animated.timing(animated, {
      toValue: value,
      duration,
      useNativeDriver: false,
    });

    anim.start();

    return () => {
      animated.removeListener(listenerId);
      anim.stop();
    };
  }, [value, duration, animated]);

  return (
    <Text
      style={[{ fontVariant: ['tabular-nums'] }, style]}
      {...props}>
      {display}
    </Text>
  );
}
