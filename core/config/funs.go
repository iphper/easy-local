package config

// Get 获取配置信息
func Get(key string) any {
	return instance.Get(key)
}

// Set 设置配置信息
func Set(key string, val any) {
	instance.Set(key, val)
}
