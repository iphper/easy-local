package mapper

// DeepMerge Map的深度合并
func DeepMerge(dst, src map[string]interface{}) map[string]interface{} {
	for k, v := range src {
		if vMap, ok := v.(map[string]interface{}); ok {
			// src 是 map
			if dstMap, ok := dst[k].(map[string]interface{}); ok {
				// dst 也是 map → 递归合并
				dst[k] = DeepMerge(dstMap, vMap)
			} else {
				// dst 不是 map → 直接覆盖（拷贝一份）
				dst[k] = DeepMerge(make(map[string]interface{}), vMap)
			}
		} else {
			// 普通值 → 覆盖
			dst[k] = v
		}
	}
	return dst
}
