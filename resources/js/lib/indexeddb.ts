/**
 * IndexedDB Utilities for Offline-First PWA
 * Manages local caching and sync queue
 */

export interface SyncQueueItem {
  id?: number;
  endpoint: string;
  method: string;
  payload: Record<string, unknown>;
  timestamp: number;
  retries: number;
}

export interface CacheConfig {
  name: string;
  version: number;
  stores: Record<string, CacheStore>;
}

export interface CacheStore {
  name: string;
  keyPath: string;
  indexes?: Array<{ name: string; keyPath: string }>;
  ttl?: number; // Time to live in milliseconds
}

const DB_NAME = 'rrmcs_db';
const DB_VERSION = 1;

export class IndexedDBManager {
  private db: IDBDatabase | null = null;

  /**
   * Initialize IndexedDB connection
   */
  async init(): Promise<void> {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        resolve();
      };

      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;

        // Sync queue store
        if (!db.objectStoreNames.contains('sync_queue')) {
          const syncStore = db.createObjectStore('sync_queue', {
            keyPath: 'id',
            autoIncrement: true,
          });
          syncStore.createIndex('endpoint', 'endpoint', { unique: false });
          syncStore.createIndex('timestamp', 'timestamp', { unique: false });
        }

        // Cache stores for different data types
        if (!db.objectStoreNames.contains('rakes')) {
          const rakeStore = db.createObjectStore('rakes', { keyPath: 'id' });
          rakeStore.createIndex('siding_id', 'siding_id', { unique: false });
          rakeStore.createIndex('rake_number', 'rake_number', { unique: true });
        }

        if (!db.objectStoreNames.contains('vehicles')) {
          const vehicleStore = db.createObjectStore('vehicles', { keyPath: 'id' });
          vehicleStore.createIndex('vehicle_number', 'vehicle_number', { unique: true });
          vehicleStore.createIndex('rfid_tag', 'rfid_tag', { unique: false });
        }

        if (!db.objectStoreNames.contains('indents')) {
          const indentStore = db.createObjectStore('indents', { keyPath: 'id' });
          indentStore.createIndex('siding_id', 'siding_id', { unique: false });
          indentStore.createIndex('indent_number', 'indent_number', { unique: true });
        }

        if (!db.objectStoreNames.contains('coal_stock')) {
          const stockStore = db.createObjectStore('coal_stock', { keyPath: 'id' });
          stockStore.createIndex('siding_id', 'siding_id', { unique: false });
          stockStore.createIndex('as_of_date', 'as_of_date', { unique: false });
        }

        if (!db.objectStoreNames.contains('cache_metadata')) {
          db.createObjectStore('cache_metadata', { keyPath: 'key' });
        }
      };
    });
  }

  /**
   * Add item to sync queue
   */
  async addToSyncQueue(
    endpoint: string,
    method: string,
    payload: Record<string, unknown>
  ): Promise<number> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    const item: SyncQueueItem = {
      endpoint,
      method,
      payload,
      timestamp: Date.now(),
      retries: 0,
    };

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction('sync_queue', 'readwrite');
      const store = tx.objectStore('sync_queue');
      const request = store.add(item);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result as number);
    });
  }

  /**
   * Get all items from sync queue
   */
  async getSyncQueue(): Promise<SyncQueueItem[]> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction('sync_queue', 'readonly');
      const store = tx.objectStore('sync_queue');
      const request = store.getAll();

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result as SyncQueueItem[]);
    });
  }

  /**
   * Remove item from sync queue
   */
  async removeSyncQueueItem(id: number): Promise<void> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction('sync_queue', 'readwrite');
      const store = tx.objectStore('sync_queue');
      const request = store.delete(id);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve();
    });
  }

  /**
   * Cache data with TTL support
   */
  async cache(
    storeName: string,
    data: Record<string, unknown>[],
    ttlMinutes: number = 30
  ): Promise<void> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction([storeName, 'cache_metadata'], 'readwrite');
      const store = tx.objectStore(storeName);
      const metaStore = tx.objectStore('cache_metadata');

      // Clear existing data
      store.clear();

      // Add new data
      data.forEach((item) => store.add(item));

      // Update metadata with TTL
      metaStore.put({
        key: storeName,
        expiresAt: Date.now() + ttlMinutes * 60 * 1000,
      });

      tx.onerror = () => reject(tx.error);
      tx.oncomplete = () => resolve();
    });
  }

  /**
   * Get cached data (with TTL validation)
   */
  async getCache(storeName: string): Promise<Record<string, unknown>[]> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    // Check if cache is expired
    const metadata = await this.getCacheMetadata(storeName);
    if (metadata && metadata.expiresAt < Date.now()) {
      // Cache expired, return empty
      return [];
    }

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction(storeName, 'readonly');
      const store = tx.objectStore(storeName);
      const request = store.getAll();

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result as Record<string, unknown>[]);
    });
  }

  /**
   * Get cache metadata
   */
  private async getCacheMetadata(
    storeName: string
  ): Promise<{ key: string; expiresAt: number } | null> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction('cache_metadata', 'readonly');
      const store = tx.objectStore('cache_metadata');
      const request = store.get(storeName);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => resolve(request.result || null);
    });
  }

  /**
   * Clear all data
   */
  async clearAll(): Promise<void> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    const storeNames = Array.from(this.db.objectStoreNames);

    return new Promise((resolve, reject) => {
      const tx = this.db!.transaction(storeNames, 'readwrite');

      storeNames.forEach((name) => {
        tx.objectStore(name).clear();
      });

      tx.onerror = () => reject(tx.error);
      tx.oncomplete = () => resolve();
    });
  }

  /**
   * Get database size
   */
  async getSize(): Promise<number> {
    if (!this.db) throw new Error('IndexedDB not initialized');

    let totalSize = 0;
    const storeNames = Array.from(this.db.objectStoreNames);

    for (const name of storeNames) {
      const items = await this.getCache(name);
      totalSize += JSON.stringify(items).length;
    }

    return totalSize;
  }
}

// Global instance
export const indexedDB = new IndexedDBManager();
