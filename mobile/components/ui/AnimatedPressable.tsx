import { forwardRef } from 'react';
import { Pressable, type PressableProps } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';

const AnimatedPressableBase = Animated.createAnimatedComponent(Pressable);

type AnimatedPressableProps = PressableProps & {
  scaleTo?: number;
};

export const AnimatedPressable = forwardRef<React.ComponentRef<typeof Pressable>, AnimatedPressableProps>(
  function AnimatedPressable(
    {
      children,
      style,
      scaleTo = 0.97,
      disabled,
      onPressIn,
      onPressOut,
      ...props
    },
    ref,
  ) {
    const scale = useSharedValue(1);

    const animatedStyle = useAnimatedStyle(() => ({
      transform: [{ scale: scale.value }],
    }));

    return (
      <AnimatedPressableBase
        {...props}
        ref={ref}
        disabled={disabled}
        onPressIn={(event) => {
          if (!disabled) {
            scale.value = withSpring(scaleTo, { damping: 18, stiffness: 320 });
          }
          onPressIn?.(event);
        }}
        onPressOut={(event) => {
          scale.value = withSpring(1, { damping: 18, stiffness: 320 });
          onPressOut?.(event);
        }}
        style={[style, animatedStyle]}>
        {children}
      </AnimatedPressableBase>
    );
  },
);
