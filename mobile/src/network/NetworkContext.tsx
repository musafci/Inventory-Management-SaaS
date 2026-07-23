import NetInfo, { type NetInfoState } from '@react-native-community/netinfo';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';

type NetworkContextValue = {
  isConnected: boolean;
  isInternetReachable: boolean | null;
  wasOffline: boolean;
};

const NetworkContext = createContext<NetworkContextValue | null>(null);

function resolveConnected(state: NetInfoState): boolean {
  return state.isConnected === true && state.isInternetReachable !== false;
}

export function NetworkProvider({ children }: { children: ReactNode }) {
  const [isConnected, setIsConnected] = useState(true);
  const [isInternetReachable, setIsInternetReachable] = useState<boolean | null>(true);
  const [wasOffline, setWasOffline] = useState(false);
  const previousConnected = useRef(true);

  const applyState = useCallback((state: NetInfoState) => {
    const connected = resolveConnected(state);

    if (previousConnected.current && !connected) {
      setWasOffline(true);
    }

    previousConnected.current = connected;
    setIsConnected(connected);
    setIsInternetReachable(state.isInternetReachable);
  }, []);

  useEffect(() => {
    let mounted = true;

    NetInfo.fetch().then((state) => {
      if (mounted) {
        applyState(state);
      }
    });

    const unsubscribe = NetInfo.addEventListener((state) => {
      applyState(state);
    });

    return () => {
      mounted = false;
      unsubscribe();
    };
  }, [applyState]);

  const value = useMemo<NetworkContextValue>(
    () => ({
      isConnected,
      isInternetReachable,
      wasOffline,
    }),
    [isConnected, isInternetReachable, wasOffline],
  );

  return <NetworkContext.Provider value={value}>{children}</NetworkContext.Provider>;
}

export function useNetwork(): NetworkContextValue {
  const context = useContext(NetworkContext);

  if (context === null) {
    throw new Error('useNetwork must be used within NetworkProvider');
  }

  return context;
}
